# Dot.Pulse: Follow Graph Polish & New-Follower Notifications

## Context

This spec started as a request to brainstorm the follow graph from scratch. That framing turned out to be wrong: the follow graph already exists and works. `User::followers()` / `following()` / `isFollowing()` (`app/Models/User.php`) are real `BelongsToMany` relations over `pulse_followers`, `HomeFeed` (`app/Livewire/Pulse/HomeFeed.php`) already has a `followingOnly` property that filters the feed to posts by followed users, and `UserProfile` (`app/Livewire/Pulse/UserProfile.php`) already has a full `toggleFollow()`/`isFollowing`/`followersCount`/`followingCount` implementation rendered as a working Follow/Unfollow button with live counts on every profile page. None of that needs to be built.

What's real is a set of gaps between that working core and what a social platform's follow feature usually offers:

1. The "Following" filter is a plain checkbox (`resources/views/livewire/pulse/home-feed.blade.php`), not a tab — cosmetic, but worth fixing since it reads as unfinished.
2. `followingOnly` filters by followed users only. It ignores communities the viewer has joined, even though joining a community is the platform's other "I want to see this" signal.
3. The Follow button exists only on the profile page. There's no way to follow someone from the feed, a post, or a comment without navigating to their profile first.
4. `followersCount`/`followingCount` on a profile are plain numbers — there's no way to see *who* is in either list.
5. A user who follows nobody gets an empty "Following" tab with no path out of it.
6. Following someone is silent. The person being followed has no way to find out, because Dot.Pulse has no notification system of any kind yet.

Point 6 looked like the biggest lift until inspection showed otherwise: the `notifications` table already exists in the shared ecosystem PostgreSQL database (uuid `id`, `type`, polymorphic `notifiable_type`/`notifiable_id`, `data`, `read_at`, timestamps — confirmed via `Schema::getColumns('notifications')`), created by some other platform sharing the database, and `User` already `use`s Laravel's `Notifiable` trait. There is no migration for it in this repo and none is needed — Laravel's database notification channel works against exactly this shape out of the box.

## Goal

Close all six gaps without touching the parts that already work. Reuse `pulse_followers`, the existing relations, and the existing `notifications` table. Add one new relation (`User::joinedCommunities()`), one new reusable Livewire component that becomes the single source of truth for follow/unfollow (replacing the duplicated logic currently living in `UserProfile`), a follower/following list modal, a suggested-users widget, one notification class, and a notification bell — all following patterns already established elsewhere in this codebase (private Reverb channels named like `post.{id}`, the `broadcastSafely()` try/catch-and-`report()` pattern, Alpine `x-show` modals, `.dot-card`/`.dot-btn`/`.dot-badge`/`.dot-avatar` styling).

## Changes

### 1. `FollowButton` — the single source of truth for follow/unfollow

New `app/Livewire/Pulse/FollowButton.php`, a small reusable component:

```php
class FollowButton extends Component
{
    public User $user;      // the profile being followed
    public bool $compact = false;  // true when embedded inline (feed/comment rows)

    #[Computed]
    public function isFollowing(): bool { ... }   // moves here from UserProfile

    #[Computed]
    public function isSelf(): bool { return auth()->id() === $this->user->id; }

    public function toggleFollow(): void
    {
        abort_if($this->isSelf, 422, 'You cannot follow yourself.');

        $viewer = auth()->user();
        $wasFollowing = $viewer->isFollowing($this->user);

        if ($wasFollowing) {
            $viewer->following()->detach($this->user->id);
        } else {
            $viewer->following()->attach($this->user->id);
            $this->notifySafely(fn () => $this->user->notify(new NewFollower($viewer)));
        }

        unset($this->isFollowing);
        $this->dispatch('follow-toggled', userId: $this->user->id);
    }
}
```

`notifySafely()` mirrors `PostShow::broadcastSafely()` exactly (try/catch + `report($e)`) — a Reverb hiccup on the notification's broadcast leg must not roll back the follow that already succeeded, the same reasoning already applied to comment/post broadcasts three times in this codebase.

`resources/views/livewire/pulse/follow-button.blade.php` renders nothing when `isSelf` is true (matches the existing `@unless($this->isOwnProfile)` guard). Non-compact renders the same `.dot-btn .dot-btn-primary`/`.dot-btn-ghost` button UserProfile uses today. `compact` renders a smaller text-only variant (`+ Follow` / `Following`, ~11px, no icon) sized to sit inline next to a name in a feed card or comment row without unbalancing the row.

`UserProfile.php` drops `isFollowing()` and `toggleFollow()` entirely and keeps only `followersCount`/`followingCount` (page-level stats, not button state). Its view embeds `<livewire:pulse.follow-button :user="$profileUser" wire:key="follow-{{ $profileUser->id }}" />` in place of the current inline button, and listens for the sibling event to keep the count in sync:

```php
#[On('follow-toggled')]
public function refreshCounts(): void { unset($this->followersCount, $this->followingCount); }
```

This is the same sibling-component-talks-via-browser-event shape `TrendingHashtags` → `HomeFeed` already uses for `filter-by-hashtag`.

`FollowButton` is then dropped inline, `compact`, next to the author name in:
- `resources/views/livewire/pulse/home-feed.blade.php` (post card author row)
- `resources/views/livewire/pulse/post-show.blade.php` (post detail author row)
- `resources/views/livewire/pulse/partials/comment.blade.php` (comment author row)

Each needs its own `wire:key` (`follow-{{ $post->author->id }}-feed`, etc. — unique per render context, not just per user, since the same author can appear multiple times on one page) to avoid Livewire component-id collisions.

### 2. Following tab includes joined communities

`User` gains a `joinedCommunities(): BelongsToMany`, mirroring `Community::members()`:

```php
public function joinedCommunities(): BelongsToMany
{
    return $this->belongsToMany(Community::class, 'community_memberships')->withTimestamps();
}
```

`HomeFeed::posts()`'s `followingOnly` branch changes from a single `whereIn` to an or-grouped pair, so a post reaches the Following tab if its author is followed *or* its community is joined:

```php
->when($this->followingOnly, function ($q) {
    $followingIds = auth()->user()->following()->pluck('users.id');
    $communityIds = auth()->user()->joinedCommunities()->pluck('communities.id');

    $q->where(function ($q2) use ($followingIds, $communityIds) {
        $q2->whereIn('user_id', $followingIds)
            ->orWhereIn('community_id', $communityIds);
    });
})
```

### 3. Checkbox → tabs

`home-feed.blade.php`'s `<input type="checkbox" wire:model.live="followingOnly">` is replaced with a two-item tab control ("For You" / "Following"), each a button doing `wire:click="$set('followingOnly', false|true)"` with an active-state style matching the existing `.nav-item.active` treatment used in the sidebar. `HomeFeed.php` itself is unchanged beyond the query in #2 — `followingOnly` stays a plain `public bool`.

### 4. Follower/following lists

New `app/Livewire/Pulse/FollowList.php`:

```php
class FollowList extends Component
{
    public User $user;
    public string $mode; // 'followers' | 'following'

    #[Computed]
    public function people(): Collection
    {
        $relation = $this->mode === 'followers' ? 'followers' : 'following';

        // orderByPivot, not orderBy: 'created_at' is ambiguous between
        // `users` and the `pulse_followers` pivot once the pivot's own
        // withTimestamps() columns are in play — without qualifying it,
        // Postgres has no guaranteed order at all, silently contradicting
        // the "most recent" claim these results are presented under.
        return $this->user->{$relation}()
            ->with('pulseProfile')
            ->orderByPivot('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
```

Capped at 50, consistent with this codebase's existing precedent for list limits (`HomeFeed` caps at 30, `UserProfile`'s own posts list at 20) rather than paginating — a "showing the most recent 50" note covers the rare overflow case.

On the profile page, the two `<span>...followers</span>` / `<span>...following</span>` become buttons (`wire:click` toggling a local `$showList` + `$listMode` pair on `UserProfile`, Alpine `x-show` on the modal wrapper — the exact pattern already verified working for Jetstream's delete-account modal). Opening either mounts `<livewire:pulse.follow-list :user="$profileUser" :mode="$listMode" wire:key="list-{{ $listMode }}" />` inside the modal. Each row is a `.dot-row` with avatar, name, headline, linking to that person's profile, with a `compact` `FollowButton` on the right so you can follow someone directly from the list.

### 5. Suggested users in the empty Following state

New `app/Livewire/Pulse/SuggestedUsers.php`:

```php
#[Computed]
public function suggestions(): Collection
{
    $followingIds = auth()->user()->following()->pluck('users.id');

    return User::query()
        ->whereKeyNot(auth()->id())
        ->whereNotIn('id', $followingIds)
        ->with('pulseProfile')
        ->get()
        ->sortByDesc(fn (User $u) => $u->pulseProfile?->community_points ?? 0)
        ->take(5);
}
```

Sorting in PHP after a full fetch (rather than a DB-level join/orderBy on a related table's column) is deliberately simple and matches the data volumes this app currently runs at — the same pragmatic choice already made elsewhere in this codebase (see `AiModerationService`'s synchronous, non-queued Claude calls). Worth revisiting with a real query (`leftJoin`/`orderByDesc`) if the user table grows large enough for this to matter.

Rendered only where `HomeFeed`'s `followingOnly` is true and `$this->posts` is empty, replacing the generic "No posts yet" card with a short message ("Follow people to see their posts here") plus this widget's list of five suggested people, each with a `compact` `FollowButton`.

### 6. `NewFollower` notification

New `app/Notifications/NewFollower.php`:

```php
class NewFollower extends Notification
{
    public function __construct(public User $follower) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_follower',
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    // Echo/Reverb event name — mirrors CommentPosted::broadcastAs() /
    // PostPublished::broadcastAs() exactly. Without this, Laravel falls
    // back to the fully-qualified BroadcastNotificationCreated class name
    // as the event name, which pulse-realtime.js's Echo listener would
    // have no way to match.
    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    // NOT the event name (that's broadcastAs() above) — this is the
    // `type` key inside the wire payload. It has to be set here, not just
    // inside toBroadcast()'s array: Illuminate\Notifications\Events\
    // BroadcastNotificationCreated::broadcastWith() does
    // array_merge($this->data, ['id' => ..., 'type' => $this->broadcastType()])
    // on the way out, which silently overwrites whatever 'type' toBroadcast()
    // set. Confirmed by reading vendor/laravel/framework's
    // Notifications/Events/BroadcastNotificationCreated.php directly.
    public function broadcastType(): string
    {
        return 'new_follower';
    }
}
```

The same `array_merge` also injects `'id' => $this->notification->id` into the outgoing payload for free — the bell can use that uuid to reconcile a live broadcast with the eventual row from `notifications()->latest()` if it ever needs to (not required for the simple "just refetch" approach in §8, but there if needed).

Denormalizing `follower_name` into `data` (rather than just storing `follower_id` and joining at render time) keeps the bell dropdown a single `notifications()` query with no N+1 user lookups — the same reasoning `CommentPosted::broadcastWith()` already applies by including `author_name` directly in its payload instead of just an id.

### 7. Broadcast channel

`User` gains:

```php
public function receivesBroadcastNotificationsOn(?Notification $notification = null): string
{
    return 'user.'.$this->id;
}
```

The `$notification` parameter is optional and unused here, but matches Laravel's real signature: `BroadcastNotificationCreated::channelName()` (`vendor/laravel/framework/.../Notifications/Events/BroadcastNotificationCreated.php`) calls `$this->notifiable->receivesBroadcastNotificationsOn($this->notification)`, passing the notification instance — a zero-arg override would still work today (PHP doesn't enforce a max argument count), but the real signature is worth matching in case a future notification type needs to route to a different channel based on which notification it is.

This overrides Laravel's default `App.Models.User.{id}` channel name so it matches this codebase's existing short-form convention (`post.{postId}`) rather than mixing two naming schemes.

`BroadcastServiceProvider::requireChannels()` gains a second channel, authorized only for that exact user, following the same fail-closed, non-int-typed-parameter shape already used for `post.{postId}`:

```php
Broadcast::channel('user.{userId}', function (User $user, $userId) {
    return is_string($userId) && ctype_digit($userId) && (string) $user->id === $userId;
});
```

### 8. Notification bell

New `app/Livewire/Pulse/NotificationBell.php`:

```php
class NotificationBell extends Component
{
    #[Computed]
    public function unreadCount(): int { return auth()->user()->unreadNotifications()->count(); }

    #[Computed]
    public function recent(): Collection { return auth()->user()->notifications()->latest()->limit(20)->get(); }

    #[On('notification-received')]
    public function refresh(): void { unset($this->unreadCount, $this->recent); }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->recent);
    }
}
```

`unreadNotifications`/`notifications`/`markAsRead()` all come free from Laravel's `Notifiable` trait — no custom query code needed beyond what's above.

Mounted once in `layouts/app.blade.php`'s `<header class="topbar">`, as a new `.topbar-btn` (bell icon, `material-symbols-rounded` `notifications`) placed before the existing profile link, with a small badge (reusing `.dot-badge-accent`'s color, shrunk to a dot/number) shown when `unreadCount > 0`. Clicking toggles an Alpine `x-show` dropdown — same modal idiom as everywhere else in this pass — listing `recent` (avatar, "**{{ follower_name }}** started following you", relative time, unread rows visually distinguished by a subtle left accent bar), each linking to `route('users.show', ...)` via the stored `follower_id`. A "Mark all read" text button calls `markAllRead`.

### 9. Realtime wiring

`layouts/app.blade.php`'s `<body>` tag gains `data-user-id="{{ auth()->id() }}"` when authenticated — the same convention `PostShow` already established with `[data-post-id]` for `pulse-realtime.js` to key off of.

`resources/js/pulse-realtime.js` gains a third subscription, alongside the existing public `pulse-feed` and per-post-page-only `post.{id}` ones:

```js
const userId = document.body.dataset.userId;

if (userId) {
    window.Echo.private(`user.${userId}`).listen('.notification.new', () => {
        window.Livewire.dispatch('notification-received');
    });
}
```

`NotificationBell` is mounted on every authenticated page (the topbar is shared layout), so — unlike the per-post `post.{id}` channel, which is genuinely a no-op subscription on pages without `PostShow` mounted — this one always has a live listener, matching the doc comment already in `pulse-realtime.js` about initializing shell subscriptions from something mounted on every authenticated page.

## Testing

- `FollowButton`: following a user attaches the pivot row and fires `NewFollower`; unfollowing detaches and fires nothing; a user cannot follow themselves (422); toggling twice returns to the original state; a broadcast failure on the notification (simulated) doesn't prevent the `pulse_followers` row from being written.
- `HomeFeed` Following tab: a post from a followed user appears; a post from a joined community (whose author is *not* followed) appears; a post from neither appears only in "For You"; empty Following state renders `SuggestedUsers` instead of the generic empty card.
- `FollowList`: opening "followers" vs "following" on a profile shows the correct set capped at 50; each row's `FollowButton` reflects and updates the *viewer's* relationship to that row's person, independent of the modal owner.
- `SuggestedUsers`: excludes the viewer and everyone already followed; orders by `community_points` descending.
- `NotificationBell`: a fresh follow bumps `unreadCount` and appears in `recent` without a page reload (verify via two authenticated browser sessions, matching how the Alpine fix was verified last pass); `markAllRead` zeroes the count and clears the accent styling; the `user.{id}` channel rejects a different authenticated user (verify the `Broadcast::channel` closure directly, same style as the existing `post.{postId}` closure has no direct test today — manual verification is consistent with current coverage).

## Explicitly out of scope

- Follow requests / approval / private accounts — `pulse_followers` has no `status` column, only `follower_id`/`following_id`; supporting this would mean a schema change, which this spec deliberately avoids.
- Notifications for anything other than new-follower (comments, reactions, moderation decisions, mentions) — the bell and `notifications` table are generic and will hold future notification types without changes, but only `NewFollower` ships now.
- Email or push notifications — database + in-app real-time only.
- A persistent "suggested users" sidebar widget shown at all times — scoped only to the empty Following-tab state, per the actual gap identified.
- Pagination on the follower/following list modal — capped at 50 with a "most recent" note, matching this codebase's existing list-limit precedent everywhere else.
- A dedicated full-page notification history/inbox — the topbar dropdown (last 20) is the only surface; a "view all" page is a natural, separate follow-up once there's more than one notification type to browse.
