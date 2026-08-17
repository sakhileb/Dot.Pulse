# Follow Graph Polish & New-Follower Notifications Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close six real gaps around Dot.Pulse's already-working follow graph — a reusable follow button usable anywhere, a Following feed that also includes joined communities, browsable follower/following lists, a suggested-users empty state, and a live-updating notification bell when someone follows you.

**Architecture:** One new `FollowButton` Livewire component becomes the single source of truth for follow/unfollow (replacing logic duplicated in `UserProfile`) and gets embedded, `compact`, everywhere an author's name appears. A `NewFollower` database+broadcast notification rides Laravel's existing `Notifiable` trait and a `notifications` table that already exists in the shared production database (a defensively-guarded migration backfills it for fresh/test databases). A new private `user.{id}` Reverb channel, matching this codebase's existing `post.{id}` naming convention, pushes the notification to a topbar bell in real time.

**Tech Stack:** Laravel 12/13 (PHP 8.4/8.5), Livewire 3.8.2, Alpine.js (bundled via `@livewireScripts`, not a second CDN load), Laravel Reverb/Echo, PostgreSQL (prod, shared ecosystem DB) / SQLite in-memory (tests via `RefreshDatabase`).

## Global Constraints

- Every new Blade view uses this codebase's existing inline-style + shared class convention (`.dot-card`, `.dot-btn`/`.dot-btn-primary`/`.dot-btn-ghost`, `.dot-badge*`, `.dot-avatar`, `.dot-row`, `.topbar-btn`), never Tailwind utility classes — none of the Pulse-namespaced views in this repo use them.
- Every `<livewire:...>` tag rendered inside a `@foreach` (or that could appear more than once per page) gets its own unique `wire:key` — a duplicate key across renders silently corrupts Livewire's DOM diffing.
- Anything that broadcasts (events or notifications) over Reverb is wrapped in `try/catch` + `report($e)` at the call site — a Reverb hiccup must never fail the primary database write it rides alongside. This codebase already does this three times (`PostShow::broadcastSafely()`, `ModerationQueue::approve()`, `AiModerationService::upsertEnrichment()`); follow the identical shape, don't introduce a new abstraction for it.
- Modals are Alpine `x-show`/`x-data`, never a second Alpine load — `layouts/app.blade.php`'s only Alpine instance comes from `@livewireScripts` (see the comment already in that file; this was a real, recently-fixed bug).
- Tests live in `tests/Feature/Pulse/` (namespace `Tests\Feature\Pulse`) for anything Pulse-specific, or `tests/Feature/` for cross-cutting concerns like broadcast channel auth — matching where the equivalent existing test already lives. All use `RefreshDatabase`. There are no model factories for `PulseProfile`, `Community`, or `CommunityMembership` — construct them with `::create([...])` directly, matching every existing test. Users are always `User::factory()->withPersonalTeam()->create()` — `PulsePost::create()` always needs `user_id`, `team_id`, `type`, `body`, `status`.
- `phpunit.xml` forces `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` — the shared production `notifications` table does not exist in the test database; it must come from a migration in this repo (Task 1).

---

## Task 1: Notifications table migration

The `notifications` table already exists in the shared ecosystem PostgreSQL database — created by a different platform sharing it, in exactly Laravel's standard shape (confirmed via `Schema::getColumns('notifications')` against the real dev DB: uuid `id`, `type`, polymorphic `notifiable_type`/`notifiable_id`, `data`, `read_at`, timestamps). There is no migration for it in this repo, so the test database (SQLite, fresh per `RefreshDatabase`) has no such table at all. This migration backfills it, guarded so it's a safe no-op against the shared production database where it already exists.

**Files:**
- Create: `database/migrations/2026_08_17_000001_create_notifications_table.php`
- Test: `tests/Feature/NotificationsTableTest.php`

**Interfaces:**
- Produces: a `notifications` table matching Laravel's `Notifiable` trait expectations (consumed by every later task that calls `->notify()` or queries `->notifications()`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The notifications table already exists in the shared ecosystem
 * PostgreSQL database (created by another platform sharing it) -- this
 * repo has no migration for it, so a fresh test database has no such
 * table at all. See docs/superpowers/specs/
 * 2026-08-17-follow-graph-polish-and-notifications.md.
 */
class NotificationsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_notifications_table_exists_with_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasColumns('notifications', [
            'id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at',
        ]));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/NotificationsTableTest.php`
Expected: FAIL — `Schema::hasTable('notifications')` is false (no such table in the fresh SQLite test database).

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * See tests/Feature/NotificationsTableTest.php and
 * docs/superpowers/specs/2026-08-17-follow-graph-polish-and-notifications.md
 * for why this table has no migration already, and why this one is
 * guarded with hasTable() rather than a plain Schema::create().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/NotificationsTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_17_000001_create_notifications_table.php tests/Feature/NotificationsTableTest.php
git commit -m "feat: add defensively-guarded notifications table migration"
```

---

## Task 2: `User` model — `joinedCommunities()` and `receivesBroadcastNotificationsOn()`

**Files:**
- Modify: `app/Models/User.php`
- Modify: `tests/Feature/Pulse/PulseTest.php`

**Interfaces:**
- Produces: `User::joinedCommunities(): BelongsToMany` (consumed by Task 8's Following-feed merge query). `User::receivesBroadcastNotificationsOn(): string` (consumed implicitly by Laravel's notification broadcasting internals once Task 4/11 send a notification with the `broadcast` channel).

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Pulse/PulseTest.php`, right after the existing `test_user_can_join_community` (same file already imports `Community`, `CommunityMembership`, `User`):

```php
    public function test_user_has_joined_communities_relationship(): void
    {
        $community = Community::create([
            'created_by' => $this->user->id,
            'name'       => 'Mining Ops',
            'slug'       => 'mining-ops',
            'visibility' => 'public',
        ]);

        CommunityMembership::create([
            'community_id' => $community->id,
            'user_id'      => $this->user->id,
            'role'         => 'member',
        ]);

        $this->assertTrue($this->user->joinedCommunities->contains($community));
    }

    public function test_a_user_receives_broadcast_notifications_on_their_own_short_form_channel(): void
    {
        $this->assertSame('user.'.$this->user->id, $this->user->receivesBroadcastNotificationsOn());
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/PulseTest.php`
Expected: FAIL — `joinedCommunities` and `receivesBroadcastNotificationsOn` don't exist on `User` yet (`Call to undefined method`/`BadMethodCallException`).

- [ ] **Step 3: Add the two methods to `User`**

In `app/Models/User.php`, add the import `use Illuminate\Notifications\Notification;` alongside the existing `use` statements, and add both methods after the existing `isFollowing()` method:

```php
    /**
     * Mirrors Community::members() -- lets HomeFeed's Following tab
     * (Task 8) merge in posts from communities the viewer has joined,
     * not just posts from people they follow.
     */
    public function joinedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_memberships')->withTimestamps();
    }

    /**
     * Overrides Laravel's default `App.Models.User.{id}` broadcast channel
     * name so it matches this codebase's existing short-form convention
     * (see App\Providers\BroadcastServiceProvider's post.{postId} channel)
     * instead of mixing two naming schemes. The optional $notification
     * parameter matches Laravel's real call site --
     * Illuminate\Notifications\Events\BroadcastNotificationCreated::channelName()
     * calls this with the notification instance -- even though it's
     * unused today.
     */
    public function receivesBroadcastNotificationsOn(?Notification $notification = null): string
    {
        return 'user.'.$this->id;
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/PulseTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php tests/Feature/Pulse/PulseTest.php
git commit -m "feat: add User::joinedCommunities() and receivesBroadcastNotificationsOn()"
```

---

## Task 3: Broadcast channel authorization for `user.{userId}`

**Files:**
- Modify: `app/Providers/BroadcastServiceProvider.php`
- Modify: `tests/Feature/BroadcastChannelAuthorizationTest.php`

**Interfaces:**
- Produces: a private `user.{userId}` Reverb channel that only the matching authenticated user can subscribe to (consumed by Task 12's frontend Echo listener).

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/BroadcastChannelAuthorizationTest.php`, after the existing `test_unauthenticated_user_cannot_authorize_any_private_channel`:

```php
    public function test_a_user_can_authorize_their_own_notification_channel(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->authRequest($user, "private-user.{$user->id}")->assertOk();
    }

    public function test_a_user_cannot_authorize_someone_elses_notification_channel(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();

        $this->authRequest($stranger, "private-user.{$user->id}")->assertForbidden();
    }

    public function test_a_non_numeric_notification_channel_identifier_fails_closed(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->authRequest($user, 'private-user.not-a-number')->assertForbidden();
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/BroadcastChannelAuthorizationTest.php`
Expected: FAIL — no `user.{userId}` channel is registered, so `/broadcasting/auth` returns forbidden for all three, including the one expected to succeed.

- [ ] **Step 3: Register the channel**

In `app/Providers/BroadcastServiceProvider.php`, inside `requireChannels()`, after the existing `post.{postId}` channel registration:

```php
        /**
         * App\Notifications\NewFollower broadcasts here (via
         * User::receivesBroadcastNotificationsOn()). Same fail-closed,
         * non-int-typed-parameter shape as post.{postId} above -- only
         * the user themselves may ever subscribe to their own
         * notification stream.
         */
        Broadcast::channel('user.{userId}', function (User $user, $userId) {
            if (! is_string($userId) || ! ctype_digit($userId)) {
                return false;
            }

            return (string) $user->id === $userId;
        });
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/BroadcastChannelAuthorizationTest.php`
Expected: PASS (all tests in the file, including the pre-existing `post.{postId}` ones)

- [ ] **Step 5: Commit**

```bash
git add app/Providers/BroadcastServiceProvider.php tests/Feature/BroadcastChannelAuthorizationTest.php
git commit -m "feat: authorize the private user.{userId} notification channel"
```

---

## Task 4: `NewFollower` notification

This is the trickiest piece in the whole plan to get right: Laravel's `BroadcastNotificationCreated::broadcastWith()` does `array_merge($this->data, ['id' => ..., 'type' => $this->broadcastType()])` on its way out over the wire — which silently overwrites whatever `'type'` key `toBroadcast()` set. The event *name* Echo listens for comes from a completely different method, `broadcastAs()`. Getting these two confused (or omitting either) breaks the live bell in a way that fails silently — no error, the event just never arrives client-side. Both are written out explicitly below with the reasoning inline; this was confirmed by reading `vendor/laravel/framework/src/Illuminate/Notifications/Events/BroadcastNotificationCreated.php` directly, not from memory of the API.

**Files:**
- Create: `app/Notifications/NewFollower.php`
- Test: `tests/Feature/Pulse/NewFollowerNotificationTest.php`

**Interfaces:**
- Consumes: `App\Models\User` (constructor param).
- Produces: `NewFollower` — constructed as `new NewFollower(User $follower)`. `via()` returns `['database', 'broadcast']`. `toDatabase()` shape: `['type' => 'new_follower', 'follower_id' => int, 'follower_name' => string]` (consumed by Task 11's `NotificationBell` view). `broadcastAs()` returns `'notification.new'` (consumed by Task 12's Echo listener — the two must match exactly). `broadcastType()` returns `'new_follower'`.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Pulse;

use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewFollowerNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_a_database_notification_with_the_followers_details(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create(['name' => 'Ada Lovelace']);

        $target->notify(new NewFollower($follower));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $target->id,
            'notifiable_type' => User::class,
            'type'            => NewFollower::class,
        ]);

        $notification = $target->notifications()->first();
        $this->assertSame('new_follower', $notification->data['type']);
        $this->assertSame($follower->id, $notification->data['follower_id']);
        $this->assertSame('Ada Lovelace', $notification->data['follower_name']);
        $this->assertTrue($notification->unread());
    }

    public function test_the_broadcast_event_name_and_payload_type_are_set_correctly(): void
    {
        $follower = User::factory()->withPersonalTeam()->create();
        $notification = new NewFollower($follower);

        // broadcastAs() is the Echo event name pulse-realtime.js listens
        // for; broadcastType() is a separate hook for the payload's
        // 'type' key -- see this task's own docblock above for why both
        // are required.
        $this->assertSame('notification.new', $notification->broadcastAs());
        $this->assertSame('new_follower', $notification->broadcastType());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/NewFollowerNotificationTest.php`
Expected: FAIL — `App\Notifications\NewFollower` doesn't exist (`Class not found`).

- [ ] **Step 3: Write the notification class**

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * See docs/superpowers/specs/2026-08-17-follow-graph-polish-and-notifications.md
 * and this notification's own test file for why broadcastAs() (the Echo
 * event name) and broadcastType() (the wire payload's 'type' key) are
 * both required and serve different purposes.
 */
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
            'type'          => 'new_follower',
            'follower_id'   => $this->follower->id,
            'follower_name' => $this->follower->name,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastType(): string
    {
        return 'new_follower';
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/NewFollowerNotificationTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Notifications/NewFollower.php tests/Feature/Pulse/NewFollowerNotificationTest.php
git commit -m "feat: add NewFollower database+broadcast notification"
```

---

## Task 5: `FollowButton` component (standalone)

Creates the reusable component fresh, tested on its own. `UserProfile` still has its own working `toggleFollow()`/`isFollowing` at the end of this task — the cutover happens in Task 6, kept separate so each step stays independently reviewable and revertible.

**Files:**
- Create: `app/Livewire/Pulse/FollowButton.php`
- Create: `resources/views/livewire/pulse/follow-button.blade.php`
- Test: `tests/Feature/Pulse/FollowButtonTest.php`

**Interfaces:**
- Consumes: `App\Models\User` (public `$user` prop), `App\Notifications\NewFollower` (Task 4).
- Produces: `<livewire:pulse.follow-button :user="$someUser" :compact="true|false" wire:key="..." />` — consumed by Tasks 6, 7, 9, 10. Dispatches browser event `follow-toggled` with payload `userId: int` (consumed by Task 6).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\FollowButton;
use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class FollowButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_follow_another_user(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($follower)
            ->test(FollowButton::class, ['user' => $target])
            ->call('toggleFollow');

        $this->assertDatabaseHas('pulse_followers', [
            'follower_id'  => $follower->id,
            'following_id' => $target->id,
        ]);
        $this->assertTrue($follower->fresh()->isFollowing($target));
    }

    public function test_following_toggles_off_on_a_second_call(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();

        $component = Livewire::actingAs($follower)->test(FollowButton::class, ['user' => $target]);

        $component->call('toggleFollow');
        $this->assertTrue($follower->fresh()->isFollowing($target));

        $component->call('toggleFollow');
        $this->assertFalse($follower->fresh()->isFollowing($target));
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($user)
            ->test(FollowButton::class, ['user' => $user])
            ->call('toggleFollow')
            ->assertStatus(422);

        $this->assertDatabaseCount('pulse_followers', 0);
    }

    public function test_toggling_dispatches_the_cross_component_browser_event(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($follower)
            ->test(FollowButton::class, ['user' => $target])
            ->call('toggleFollow')
            ->assertDispatched('follow-toggled', userId: $target->id);
    }

    public function test_following_a_user_sends_them_a_new_follower_notification(): void
    {
        Notification::fake();

        $target = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($follower)
            ->test(FollowButton::class, ['user' => $target])
            ->call('toggleFollow');

        Notification::assertSentTo($target, NewFollower::class, fn (NewFollower $n) => $n->follower->is($follower));
    }

    public function test_unfollowing_does_not_send_a_notification(): void
    {
        Notification::fake();

        $target = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();
        $follower->following()->attach($target->id);

        Livewire::actingAs($follower)
            ->test(FollowButton::class, ['user' => $target])
            ->call('toggleFollow');

        Notification::assertNotSentTo($target, NewFollower::class);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/FollowButtonTest.php`
Expected: FAIL — `App\Livewire\Pulse\FollowButton` doesn't exist.

- [ ] **Step 3: Write the component**

```php
<?php

namespace App\Livewire\Pulse;

use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The single source of truth for follow/unfollow -- embedded, compact,
 * next to any author's name (feed, post detail, comments, follower/
 * following lists, suggested users) as well as full-size on a profile
 * page. See docs/superpowers/specs/
 * 2026-08-17-follow-graph-polish-and-notifications.md.
 */
class FollowButton extends Component
{
    public User $user;

    public bool $compact = false;

    #[Computed]
    public function isFollowing(): bool
    {
        return auth()->check() && auth()->user()->isFollowing($this->user);
    }

    #[Computed]
    public function isSelf(): bool
    {
        return auth()->id() === $this->user->id;
    }

    public function toggleFollow(): void
    {
        abort_if($this->isSelf, 422, 'You cannot follow yourself.');

        $viewer = auth()->user();

        if ($viewer->isFollowing($this->user)) {
            $viewer->following()->detach($this->user->id);
        } else {
            $viewer->following()->attach($this->user->id);
            $this->notifySafely();
        }

        unset($this->isFollowing);
        $this->dispatch('follow-toggled', userId: $this->user->id);
    }

    /**
     * Mirrors PostShow::broadcastSafely() exactly -- NewFollower's
     * broadcast leg over Reverb must not roll back the pulse_followers
     * row that already committed above it. Not separately unit-tested
     * for the failure path (same as the three existing call sites this
     * mirrors) -- a Mockery partial mock on $this->user wouldn't survive
     * Livewire's snapshot/hydrate cycle between test calls, so this is
     * verified by code review plus the manual two-tab pass at the end of
     * Task 12, consistent with how this codebase already treats this
     * category of resilience.
     */
    private function notifySafely(): void
    {
        try {
            $this->user->notify(new NewFollower(auth()->user()));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render(): View
    {
        return view('livewire.pulse.follow-button');
    }
}
```

- [ ] **Step 4: Write the view**

```blade
@unless($this->isSelf)
    @if($compact)
        <button wire:click="toggleFollow" style="background:none;border:none;font-size:11px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;color:{{ $this->isFollowing ? '#71717a' : '#f1c62e' }};padding:0;">
            {{ $this->isFollowing ? 'Following' : '+ Follow' }}
        </button>
    @else
        <button wire:click="toggleFollow" class="dot-btn {{ $this->isFollowing ? 'dot-btn-ghost' : 'dot-btn-primary' }}" style="flex-shrink:0;">
            {{ $this->isFollowing ? 'Following' : 'Follow' }}
        </button>
    @endif
@endunless
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/FollowButtonTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Pulse/FollowButton.php resources/views/livewire/pulse/follow-button.blade.php tests/Feature/Pulse/FollowButtonTest.php
git commit -m "feat: add reusable FollowButton component"
```

---

## Task 6: Cut `UserProfile` over to `FollowButton`

Removes the duplicated `toggleFollow()`/`isFollowing` from `UserProfile` (now redundant with Task 5), embeds the new component, and keeps the profile's follower/following counts in sync via the `follow-toggled` browser event — the same sibling-component pattern `TrendingHashtags` → `HomeFeed` already uses for `filter-by-hashtag`.

**Files:**
- Modify: `app/Livewire/Pulse/UserProfile.php`
- Modify: `resources/views/livewire/pulse/user-profile.blade.php`
- Modify: `tests/Feature/Pulse/UserProfileTest.php`

**Interfaces:**
- Consumes: `FollowButton` (Task 5), its `follow-toggled` event.
- Produces: `UserProfile::refreshCounts()` — no other task depends on this directly.

- [ ] **Step 1: Update the tests first**

In `tests/Feature/Pulse/UserProfileTest.php`:

Delete these three tests entirely — their behavior is now covered by `tests/Feature/Pulse/FollowButtonTest.php` (Task 5), which tests the same `toggleFollow()` logic directly on its new home:
- `test_a_user_can_follow_another_user`
- `test_following_toggles_off_on_a_second_call`
- `test_a_user_cannot_follow_themselves`

Keep `test_a_profile_page_is_viewable`, `test_follower_and_following_counts_are_accurate`, `test_profile_shows_only_published_posts`, and `test_the_following_only_feed_filter_only_shows_followed_authors` unchanged.

Add this new test, right after `test_follower_and_following_counts_are_accurate`:

```php
    public function test_follower_count_refreshes_when_it_receives_the_follow_toggled_event(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $viewer = User::factory()->withPersonalTeam()->create();

        $component = Livewire::actingAs($viewer)
            ->test(UserProfile::class, ['profileUser' => $target])
            ->assertSet('followersCount', 0);

        $viewer->following()->attach($target->id);

        $component->dispatch('follow-toggled', userId: $target->id)
            ->assertSet('followersCount', 1);
    }
```

- [ ] **Step 2: Run the test file to verify the new test fails and the deleted ones are gone**

Run: `php artisan test tests/Feature/Pulse/UserProfileTest.php`
Expected: the new `test_follower_count_refreshes_when_it_receives_the_follow_toggled_event` FAILS (`followersCount` stays `0` after the dispatch — nothing listens for `follow-toggled` yet); the other kept tests still PASS; the three deleted tests no longer run.

- [ ] **Step 3: Update `UserProfile.php`**

Remove the `isFollowing()` and `toggleFollow()` methods entirely (lines currently reading, from the file read earlier in this session, roughly lines 50–75 — the `#[Computed] public function isFollowing()` block through the end of `toggleFollow()`). Add the import `use Livewire\Attributes\On;` alongside the existing `Computed` import, and add this method in their place:

```php
    /**
     * FollowButton (embedded in the view in place of the old inline
     * button) owns follow/unfollow now and dispatches this browser event
     * on every toggle -- the same sibling-component-talks-via-event shape
     * TrendingHashtags uses for filter-by-hashtag.
     */
    #[On('follow-toggled')]
    public function refreshCounts(): void
    {
        unset($this->followersCount, $this->followingCount);
    }
```

`UserProfile` keeps `followersCount`, `followingCount`, `posts`, `isOwnProfile`, `mount()`, and `render()` unchanged.

- [ ] **Step 4: Update `user-profile.blade.php`**

Replace:

```blade
            @unless($this->isOwnProfile)
                <button wire:click="toggleFollow" class="dot-btn {{ $this->isFollowing ? 'dot-btn-ghost' : 'dot-btn-primary' }}" style="flex-shrink:0;">
                    {{ $this->isFollowing ? 'Following' : 'Follow' }}
                </button>
            @endunless
```

with:

```blade
            <livewire:pulse.follow-button :user="$profileUser" wire:key="follow-{{ $profileUser->id }}" />
```

(`FollowButton`'s own `@unless($this->isSelf)` guard replaces the removed `@unless($this->isOwnProfile)` — no outer guard needed here, matching how the original only had the one guard.)

- [ ] **Step 5: Run the full test file to verify it passes**

Run: `php artisan test tests/Feature/Pulse/UserProfileTest.php`
Expected: PASS (all remaining tests, including the new one)

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Pulse/UserProfile.php resources/views/livewire/pulse/user-profile.blade.php tests/Feature/Pulse/UserProfileTest.php
git commit -m "refactor: cut UserProfile over to the reusable FollowButton"
```

---

## Task 7: Embed a compact `FollowButton` in the feed, post detail, and comments

**Files:**
- Modify: `resources/views/livewire/pulse/home-feed.blade.php`
- Modify: `resources/views/livewire/pulse/post-show.blade.php`
- Modify: `resources/views/livewire/pulse/partials/comment.blade.php`
- Modify: `tests/Feature/Pulse/PulseTest.php`
- Modify: `tests/Feature/Pulse/PostShowTest.php`

**Interfaces:**
- Consumes: `FollowButton` (Task 5).

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Pulse/PulseTest.php` (add `use App\Livewire\Pulse\FollowButton;` to its imports):

```php
    public function test_the_dashboard_feed_embeds_a_follow_button_for_another_authors_post(): void
    {
        $author = User::factory()->withPersonalTeam()->create();

        PulsePost::create([
            'user_id' => $author->id,
            'team_id' => $author->currentTeam->id,
            'type'    => 'discussion',
            'body'    => 'A post from someone else',
            'status'  => 'published',
        ]);

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertSeeLivewire(FollowButton::class);
    }
```

Add to `tests/Feature/Pulse/PostShowTest.php` (add `use App\Livewire\Pulse\FollowButton;` to its imports):

```php
    public function test_the_post_page_embeds_a_follow_button_for_the_author(): void
    {
        $post = $this->publishedPost();
        $viewer = User::factory()->withPersonalTeam()->create();

        $this->actingAs($viewer)
            ->get("/posts/{$post->id}")
            ->assertSeeLivewire(FollowButton::class);
    }

    public function test_the_post_page_renders_without_error_when_a_comment_is_from_a_different_author(): void
    {
        $post = $this->publishedPost();
        $commenter = User::factory()->withPersonalTeam()->create();
        $post->comments()->create(['user_id' => $commenter->id, 'body' => 'A reply from someone else']);
        $viewer = User::factory()->withPersonalTeam()->create();

        $this->actingAs($viewer)
            ->get("/posts/{$post->id}")
            ->assertOk()
            ->assertSee('A reply from someone else');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/PulseTest.php tests/Feature/Pulse/PostShowTest.php`
Expected: the two `assertSeeLivewire(FollowButton::class)` tests FAIL (nothing embeds it yet); the comment-rendering test currently PASSES already (it's establishing a baseline before the comment partial is touched) — that's fine, it stays green throughout.

- [ ] **Step 3: Embed in `home-feed.blade.php`**

In the post card author row, change:

```blade
                                <a href="{{ route('users.show', $post->author) }}" style="font-size:13.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $post->author->name }}</a>
                                <span class="dot-badge dot-badge-accent">
```

to:

```blade
                                <a href="{{ route('users.show', $post->author) }}" style="font-size:13.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $post->author->name }}</a>
                                <livewire:pulse.follow-button :user="$post->author" :compact="true" wire:key="follow-{{ $post->author->id }}-feed-{{ $post->id }}" />
                                <span class="dot-badge dot-badge-accent">
```

(Keyed by both author id *and* post id — the same author can have more than one post in one feed render, so keying by author id alone would collide.)

- [ ] **Step 4: Embed in `post-show.blade.php`**

Change:

```blade
                    <a href="{{ route('users.show', $post->author) }}" style="font-size:13.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $post->author->name }}</a>
                    <span class="dot-badge dot-badge-accent">
```

to:

```blade
                    <a href="{{ route('users.show', $post->author) }}" style="font-size:13.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $post->author->name }}</a>
                    <livewire:pulse.follow-button :user="$post->author" :compact="true" wire:key="follow-{{ $post->author->id }}-postshow" />
                    <span class="dot-badge dot-badge-accent">
```

- [ ] **Step 5: Embed in `partials/comment.blade.php`**

Change:

```blade
            <a href="{{ route('users.show', $author) }}" style="font-size:12.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $author->name }}</a>
            @if($comment->is_solution)
```

to:

```blade
            <a href="{{ route('users.show', $author) }}" style="font-size:12.5px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $author->name }}</a>
            <livewire:pulse.follow-button :user="$author" :compact="true" wire:key="follow-{{ $author->id }}-comment-{{ $comment->id }}" />
            @if($comment->is_solution)
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/PulseTest.php tests/Feature/Pulse/PostShowTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add resources/views/livewire/pulse/home-feed.blade.php resources/views/livewire/pulse/post-show.blade.php resources/views/livewire/pulse/partials/comment.blade.php tests/Feature/Pulse/PulseTest.php tests/Feature/Pulse/PostShowTest.php
git commit -m "feat: embed a compact follow button in the feed, post detail, and comments"
```

---

## Task 8: Following tab merges joined communities; checkbox becomes a tab

**Files:**
- Modify: `app/Livewire/Pulse/HomeFeed.php`
- Modify: `resources/views/livewire/pulse/home-feed.blade.php`
- Modify: `tests/Feature/Pulse/UserProfileTest.php`

**Interfaces:**
- Consumes: `User::joinedCommunities()` (Task 2).

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Pulse/UserProfileTest.php` (add `use App\Models\Community;` and `use App\Models\CommunityMembership;` to its imports), right after `test_the_following_only_feed_filter_only_shows_followed_authors`:

```php
    public function test_the_following_tab_also_includes_posts_from_joined_communities(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();

        $community = Community::create([
            'created_by' => $viewer->id,
            'name'       => 'Fleet Ops',
            'slug'       => 'fleet-ops',
            'visibility' => 'public',
        ]);
        CommunityMembership::create([
            'community_id' => $community->id,
            'user_id'      => $viewer->id,
            'role'         => 'member',
        ]);

        $communityPoster = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();

        PulsePost::create([
            'user_id' => $communityPoster->id, 'team_id' => $communityPoster->currentTeam->id,
            'community_id' => $community->id, 'type' => 'discussion',
            'body' => 'Posted in a community I joined', 'status' => 'published',
        ]);
        PulsePost::create([
            'user_id' => $stranger->id, 'team_id' => $stranger->currentTeam->id,
            'type' => 'discussion', 'body' => 'From a stranger, no community', 'status' => 'published',
        ]);

        Livewire::actingAs($viewer)
            ->test(HomeFeed::class)
            ->set('followingOnly', true)
            ->assertSee('Posted in a community I joined')
            ->assertDontSee('From a stranger, no community');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Pulse/UserProfileTest.php --filter test_the_following_tab_also_includes_posts_from_joined_communities`
Expected: FAIL — the post from the joined community doesn't appear (the query only filters by followed user IDs today).

- [ ] **Step 3: Update the query in `HomeFeed.php`**

Replace:

```php
            ->when($this->followingOnly, fn ($q) => $q->whereIn(
                'user_id',
                auth()->user()->following()->pluck('users.id'),
            ))
```

with:

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

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pulse/UserProfileTest.php`
Expected: PASS (all tests in the file)

- [ ] **Step 5: Replace the checkbox with tabs in `home-feed.blade.php`**

Replace:

```blade
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#a1a1aa;cursor:pointer;user-select:none;">
                <input type="checkbox" wire:model.live="followingOnly" style="accent-color:#f1c62e;" />
                Following only
            </label>
```

with:

```blade
            <div style="display:flex;gap:4px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:8px;padding:3px;">
                <button wire:click="$set('followingOnly', false)" style="padding:5px 12px;border-radius:6px;border:none;font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;background:{{ ! $followingOnly ? 'rgba(241,198,46,0.12)' : 'transparent' }};color:{{ ! $followingOnly ? '#f1c62e' : '#71717a' }};">For You</button>
                <button wire:click="$set('followingOnly', true)" style="padding:5px 12px;border-radius:6px;border:none;font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;background:{{ $followingOnly ? 'rgba(241,198,46,0.12)' : 'transparent' }};color:{{ $followingOnly ? '#f1c62e' : '#71717a' }};">Following</button>
            </div>
```

This is a pure UI swap — `followingOnly` is still the same `public bool` property, so no PHP or test changes are needed for this step; the tests from Step 1 already exercise it via `->set('followingOnly', true)`, which works identically regardless of whether the frontend control is a checkbox or these buttons.

- [ ] **Step 6: Run the full test file once more to confirm nothing broke**

Run: `php artisan test tests/Feature/Pulse/UserProfileTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Pulse/HomeFeed.php resources/views/livewire/pulse/home-feed.blade.php tests/Feature/Pulse/UserProfileTest.php
git commit -m "feat: Following tab includes joined communities; checkbox becomes a tab"
```

---

## Task 9: `FollowList` — clickable follower/following lists

**Files:**
- Create: `app/Livewire/Pulse/FollowList.php`
- Create: `resources/views/livewire/pulse/follow-list.blade.php`
- Modify: `resources/views/livewire/pulse/user-profile.blade.php`
- Test: `tests/Feature/Pulse/FollowListTest.php`

**Interfaces:**
- Consumes: `FollowButton` (Task 5).
- Produces: `<livewire:pulse.follow-list :user="$someUser" mode="followers|following" wire:key="..." />`.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\FollowList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FollowListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_a_users_followers(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $followerOne = User::factory()->withPersonalTeam()->create(['name' => 'Ada Lovelace']);
        $followerTwo = User::factory()->withPersonalTeam()->create(['name' => 'Grace Hopper']);
        $followerOne->following()->attach($target->id);
        $followerTwo->following()->attach($target->id);

        $viewer = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($viewer)
            ->test(FollowList::class, ['user' => $target, 'mode' => 'followers'])
            ->assertSee('Ada Lovelace')
            ->assertSee('Grace Hopper');
    }

    public function test_it_lists_who_a_user_is_following(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();
        $followed = User::factory()->withPersonalTeam()->create(['name' => 'Katherine Johnson']);
        $viewer->following()->attach($followed->id);

        Livewire::actingAs($viewer)
            ->test(FollowList::class, ['user' => $viewer, 'mode' => 'following'])
            ->assertSee('Katherine Johnson');
    }

    public function test_it_shows_an_empty_state_with_no_followers(): void
    {
        $target = User::factory()->withPersonalTeam()->create();
        $viewer = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($viewer)
            ->test(FollowList::class, ['user' => $target, 'mode' => 'followers'])
            ->assertSee('No followers yet.');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/FollowListTest.php`
Expected: FAIL — `App\Livewire\Pulse\FollowList` doesn't exist.

- [ ] **Step 3: Write the component**

```php
<?php

namespace App\Livewire\Pulse;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FollowList extends Component
{
    public User $user;

    public string $mode;

    #[Computed]
    public function people(): Collection
    {
        $relation = $this->mode === 'followers' ? 'followers' : 'following';

        // orderByPivot, not orderBy: 'created_at' is ambiguous between
        // `users` and the pulse_followers pivot's own withTimestamps()
        // columns -- without qualifying it, there's no guaranteed order
        // at all, contradicting this being presented as "most recent".
        return $this->user->{$relation}()
            ->with('pulseProfile')
            ->orderByPivot('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.pulse.follow-list');
    }
}
```

- [ ] **Step 4: Write the view**

```blade
<div>
    @if($this->people->isEmpty())
        <p style="font-size:13px;color:#52525b;text-align:center;padding:1.5rem 0;margin:0;">
            {{ $mode === 'followers' ? 'No followers yet.' : 'Not following anyone yet.' }}
        </p>
    @else
        <div style="display:flex;flex-direction:column;gap:2px;">
            @foreach($this->people as $person)
                <div class="dot-row" style="display:flex;align-items:center;gap:10px;padding:8px 10px;">
                    <a href="{{ route('users.show', $person) }}" class="dot-avatar" style="width:32px;height:32px;font-size:12px;">
                        {{ strtoupper(substr($person->name, 0, 1)) }}
                    </a>
                    <div style="flex:1;min-width:0;">
                        <a href="{{ route('users.show', $person) }}" style="display:block;font-size:13px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $person->name }}</a>
                        @if($person->pulseProfile?->headline)
                            <p style="font-size:11.5px;color:#71717a;margin:1px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $person->pulseProfile->headline }}</p>
                        @endif
                    </div>
                    <livewire:pulse.follow-button :user="$person" :compact="true" wire:key="follow-{{ $person->id }}-list-{{ $mode }}" />
                </div>
            @endforeach
        </div>
    @endif
</div>
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/FollowListTest.php`
Expected: PASS

- [ ] **Step 6: Wire clickable counts + modals into `user-profile.blade.php`**

Wrap the whole `.dot-card` profile header block in an Alpine scope, make the follower/following counts clickable, and add two Alpine-`x-show` modals — the same idiom already used (and verified working) for Jetstream's delete-account modal, so opening/closing is pure client-side Alpine with zero extra Livewire round-trips.

Change the opening tag:

```blade
    <div class="dot-card" style="padding:1.5rem;">
```

to:

```blade
    <div class="dot-card" style="padding:1.5rem;" x-data="{ followersOpen: false, followingOpen: false }">
```

Change:

```blade
                <div style="display:flex;align-items:center;gap:18px;margin-top:14px;font-size:12px;color:#71717a;">
                    <span><strong class="metric-val" style="color:#f4f4f5;font-weight:600;">{{ $this->followersCount }}</strong> followers</span>
                    <span><strong class="metric-val" style="color:#f4f4f5;font-weight:600;">{{ $this->followingCount }}</strong> following</span>
```

to:

```blade
                <div style="display:flex;align-items:center;gap:18px;margin-top:14px;font-size:12px;color:#71717a;">
                    <button @click="followersOpen = true" style="background:none;border:none;padding:0;cursor:pointer;font:inherit;color:inherit;"><strong class="metric-val" style="color:#f4f4f5;font-weight:600;">{{ $this->followersCount }}</strong> followers</button>
                    <button @click="followingOpen = true" style="background:none;border:none;padding:0;cursor:pointer;font:inherit;color:inherit;"><strong class="metric-val" style="color:#f4f4f5;font-weight:600;">{{ $this->followingCount }}</strong> following</button>
```

By Task 6, the header card ends with the `FollowButton` embed followed by two closing `</div>`s (closing the inner flex row, then the `.dot-card` itself):

```blade
            <livewire:pulse.follow-button :user="$profileUser" wire:key="follow-{{ $profileUser->id }}" />
        </div>
    </div>
```

Insert the two modals *before* that last `</div>` (the one closing `.dot-card` itself) — not after it. `x-show`'d elements are `position:fixed;inset:0` overlays, so nesting them inside the card doesn't affect layout, and this way they stay inside the `x-data` scope from Step 1 above without re-indenting any of the card's existing, unrelated content:

```blade
            <livewire:pulse.follow-button :user="$profileUser" wire:key="follow-{{ $profileUser->id }}" />
        </div>

        <div x-show="followersOpen" x-cloak @click.self="followersOpen = false" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:50;">
            <div class="dot-card" style="width:360px;max-height:70vh;overflow-y:auto;padding:1.25rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#f4f4f5;margin:0;">Followers</h3>
                    <button @click="followersOpen = false" style="background:none;border:none;color:#71717a;cursor:pointer;font-size:18px;line-height:1;">&times;</button>
                </div>
                <livewire:pulse.follow-list :user="$profileUser" mode="followers" wire:key="list-followers-{{ $profileUser->id }}" />
            </div>
        </div>

        <div x-show="followingOpen" x-cloak @click.self="followingOpen = false" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:50;">
            <div class="dot-card" style="width:360px;max-height:70vh;overflow-y:auto;padding:1.25rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h3 style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#f4f4f5;margin:0;">Following</h3>
                    <button @click="followingOpen = false" style="background:none;border:none;color:#71717a;cursor:pointer;font-size:18px;line-height:1;">&times;</button>
                </div>
                <livewire:pulse.follow-list :user="$profileUser" mode="following" wire:key="list-following-{{ $profileUser->id }}" />
            </div>
        </div>
    </div>
```

(Note the extra closing `</div>` now at the end — it closes `.dot-card` itself, one level later than before, now that the two modals are siblings of the flex row inside it.)

- [ ] **Step 7: Run the full Pulse test suite to confirm nothing broke**

Run: `php artisan test tests/Feature/Pulse/`
Expected: PASS (all tests, including `UserProfileTest.php`)

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/Pulse/FollowList.php resources/views/livewire/pulse/follow-list.blade.php resources/views/livewire/pulse/user-profile.blade.php tests/Feature/Pulse/FollowListTest.php
git commit -m "feat: clickable follower/following lists on the profile page"
```

---

## Task 10: `SuggestedUsers` — empty Following-tab state

**Files:**
- Create: `app/Livewire/Pulse/SuggestedUsers.php`
- Create: `resources/views/livewire/pulse/suggested-users.blade.php`
- Modify: `resources/views/livewire/pulse/home-feed.blade.php`
- Test: `tests/Feature/Pulse/SuggestedUsersTest.php`
- Modify: `tests/Feature/Pulse/UserProfileTest.php`

**Interfaces:**
- Consumes: `FollowButton` (Task 5), `HomeFeed`'s `followingOnly`/`posts` (Task 8).

- [ ] **Step 1: Write the failing `SuggestedUsers` tests**

```php
<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\SuggestedUsers;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuggestedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_excludes_the_viewer_and_orders_by_points_descending(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();

        $highPoints = User::factory()->withPersonalTeam()->create(['name' => 'High Points']);
        PulseProfile::create(['user_id' => $highPoints->id, 'community_points' => 100]);

        $lowPoints = User::factory()->withPersonalTeam()->create(['name' => 'Low Points']);
        PulseProfile::create(['user_id' => $lowPoints->id, 'community_points' => 5]);

        $component = Livewire::actingAs($viewer)->test(SuggestedUsers::class);
        $names = $component->get('suggestions')->pluck('name')->all();

        $this->assertSame(['High Points', 'Low Points'], $names);
        $this->assertNotContains($viewer->name, $names);
    }

    public function test_it_excludes_users_already_followed(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();
        $alreadyFollowed = User::factory()->withPersonalTeam()->create(['name' => 'Already Followed']);
        $viewer->following()->attach($alreadyFollowed->id);

        $component = Livewire::actingAs($viewer)->test(SuggestedUsers::class);

        $this->assertNotContains('Already Followed', $component->get('suggestions')->pluck('name')->all());
    }

    public function test_it_caps_at_five_suggestions(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();
        User::factory()->withPersonalTeam()->count(7)->create();

        $component = Livewire::actingAs($viewer)->test(SuggestedUsers::class);

        $this->assertCount(5, $component->get('suggestions'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/SuggestedUsersTest.php`
Expected: FAIL — `App\Livewire\Pulse\SuggestedUsers` doesn't exist.

- [ ] **Step 3: Write the component**

```php
<?php

namespace App\Livewire\Pulse;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Shown only in HomeFeed's empty Following-tab state (see
 * resources/views/livewire/pulse/home-feed.blade.php). Sorting in PHP
 * after a full fetch, rather than a DB-level join/orderBy on a related
 * table's column, is deliberately simple and matches the data volumes
 * this app currently runs at -- the same pragmatic choice already made
 * in App\Services\AiModerationService's synchronous, non-queued Claude
 * calls. Worth revisiting with a real query if the users table grows
 * large enough for this to matter.
 */
class SuggestedUsers extends Component
{
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
            ->take(5)
            ->values();
    }

    public function render(): View
    {
        return view('livewire.pulse.suggested-users');
    }
}
```

- [ ] **Step 4: Write the view**

```blade
<div>
    @if($this->suggestions->isEmpty())
        <p style="font-size:12.5px;color:#52525b;margin:0;">No suggestions yet — check back once more people join.</p>
    @else
        <div style="display:flex;flex-direction:column;gap:2px;">
            @foreach($this->suggestions as $person)
                <div class="dot-row" style="display:flex;align-items:center;gap:10px;padding:8px 10px;">
                    <a href="{{ route('users.show', $person) }}" class="dot-avatar" style="width:32px;height:32px;font-size:12px;">
                        {{ strtoupper(substr($person->name, 0, 1)) }}
                    </a>
                    <div style="flex:1;min-width:0;">
                        <a href="{{ route('users.show', $person) }}" style="display:block;font-size:13px;font-weight:600;color:#f4f4f5;text-decoration:none;">{{ $person->name }}</a>
                        @if($person->pulseProfile?->headline)
                            <p style="font-size:11.5px;color:#71717a;margin:1px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $person->pulseProfile->headline }}</p>
                        @endif
                    </div>
                    <livewire:pulse.follow-button :user="$person" :compact="true" wire:key="follow-{{ $person->id }}-suggested" />
                </div>
            @endforeach
        </div>
    @endif
</div>
```

- [ ] **Step 5: Run `SuggestedUsersTest` to verify it passes**

Run: `php artisan test tests/Feature/Pulse/SuggestedUsersTest.php`
Expected: PASS

- [ ] **Step 6: Write the failing empty-state integration test**

Add to `tests/Feature/Pulse/UserProfileTest.php`, right after `test_the_following_tab_also_includes_posts_from_joined_communities`:

```php
    public function test_the_empty_following_tab_shows_suggested_users_instead_of_the_generic_empty_state(): void
    {
        $viewer = User::factory()->withPersonalTeam()->create();
        User::factory()->withPersonalTeam()->create(['name' => 'Suggested Person']);

        Livewire::actingAs($viewer)
            ->test(HomeFeed::class)
            ->set('followingOnly', true)
            ->assertDontSee('No posts yet. Be the first to share something with the community.')
            ->assertSee('Follow people to see their posts here.')
            ->assertSee('Suggested Person');
    }
```

Run: `php artisan test tests/Feature/Pulse/UserProfileTest.php --filter test_the_empty_following_tab_shows_suggested_users_instead_of_the_generic_empty_state`
Expected: FAIL — the empty Following tab still shows the generic "No posts yet" message.

- [ ] **Step 7: Wire the empty state into `home-feed.blade.php`**

Replace:

```blade
    @if($this->posts->isEmpty())
        <div class="dot-card" style="padding:3rem 1.5rem;text-align:center;">
            <p style="font-size:13px;color:#71717a;margin:0;">No posts yet. Be the first to share something with the community.</p>
        </div>
    @else
```

with:

```blade
    @if($this->posts->isEmpty())
        @if($followingOnly)
            <div class="dot-card" style="padding:1.5rem;">
                <p style="font-size:13px;color:#71717a;margin:0 0 1rem;text-align:center;">Follow people to see their posts here.</p>
                <livewire:pulse.suggested-users />
            </div>
        @else
            <div class="dot-card" style="padding:3rem 1.5rem;text-align:center;">
                <p style="font-size:13px;color:#71717a;margin:0;">No posts yet. Be the first to share something with the community.</p>
            </div>
        @endif
    @else
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/UserProfileTest.php tests/Feature/Pulse/SuggestedUsersTest.php`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add app/Livewire/Pulse/SuggestedUsers.php resources/views/livewire/pulse/suggested-users.blade.php resources/views/livewire/pulse/home-feed.blade.php tests/Feature/Pulse/SuggestedUsersTest.php tests/Feature/Pulse/UserProfileTest.php
git commit -m "feat: suggest users to follow in the empty Following-tab state"
```

---

## Task 11: `NotificationBell`

**Files:**
- Create: `app/Livewire/Pulse/NotificationBell.php`
- Create: `resources/views/livewire/pulse/notification-bell.blade.php`
- Modify: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/Pulse/NotificationBellTest.php`
- Modify: `tests/Feature/Pulse/PulseTest.php`

**Interfaces:**
- Consumes: `NewFollower` (Task 4)'s `toDatabase()` shape (`type`, `follower_id`, `follower_name`).
- Produces: listens for browser event `notification-received` (consumed by Task 12's Echo listener).

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\NotificationBell;
use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_count_reflects_unread_notifications(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $followerOne = User::factory()->withPersonalTeam()->create();
        $followerTwo = User::factory()->withPersonalTeam()->create();

        $user->notify(new NewFollower($followerOne));
        $user->notify(new NewFollower($followerTwo));

        $component = Livewire::actingAs($user)->test(NotificationBell::class);

        $this->assertSame(2, $component->get('unreadCount'));
        $this->assertCount(2, $component->get('recent'));
    }

    public function test_mark_all_read_zeroes_the_unread_count(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();
        $user->notify(new NewFollower($follower));

        $component = Livewire::actingAs($user)->test(NotificationBell::class);
        $this->assertSame(1, $component->get('unreadCount'));

        $component->call('markAllRead');

        $this->assertSame(0, $component->get('unreadCount'));
        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_the_notification_received_event_refreshes_the_bell(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $component = Livewire::actingAs($user)->test(NotificationBell::class);
        $this->assertSame(0, $component->get('unreadCount'));

        $follower = User::factory()->withPersonalTeam()->create();
        $user->notify(new NewFollower($follower));

        $component->dispatch('notification-received');
        $this->assertSame(1, $component->get('unreadCount'));
    }

    public function test_a_users_bell_only_shows_their_own_notifications(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();

        $stranger->notify(new NewFollower($follower));

        $component = Livewire::actingAs($user)->test(NotificationBell::class);
        $this->assertSame(0, $component->get('unreadCount'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/NotificationBellTest.php`
Expected: FAIL — `App\Livewire\Pulse\NotificationBell` doesn't exist.

- [ ] **Step 3: Write the component**

```php
<?php

namespace App\Livewire\Pulse;

use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function recent(): DatabaseNotificationCollection
    {
        return auth()->user()->notifications()->latest()->limit(20)->get();
    }

    /**
     * resources/js/pulse-realtime.js dispatches this on a live
     * App\Notifications\NewFollower broadcast (Task 12) -- mirrors
     * HomeFeed's own #[On('post-created')] pattern.
     */
    #[On('notification-received')]
    public function refresh(): void
    {
        unset($this->unreadCount, $this->recent);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->recent);
    }

    public function render(): View
    {
        return view('livewire.pulse.notification-bell');
    }
}
```

- [ ] **Step 4: Write the view**

```blade
<div x-data="{ open: false }" style="position:relative;">
    <button @click="open = !open" class="topbar-btn" title="Notifications" style="position:relative;">
        <span class="material-symbols-rounded">notifications</span>
        @if($this->unreadCount > 0)
            <span style="position:absolute;top:-2px;right:-2px;background:#f1c62e;color:#08354f;font-size:9px;font-weight:700;border-radius:100px;min-width:15px;height:15px;display:flex;align-items:center;justify-content:center;padding:0 3px;font-family:'JetBrains Mono',monospace;">{{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" class="dot-card" style="position:absolute;top:38px;right:0;width:320px;max-height:400px;overflow-y:auto;z-index:50;padding:0.75rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
            <h3 style="font-family:'Syne',sans-serif;font-size:12.5px;font-weight:700;color:#f4f4f5;margin:0;">Notifications</h3>
            @if($this->unreadCount > 0)
                <button wire:click="markAllRead" style="background:none;border:none;color:#71717a;font-size:11px;cursor:pointer;font-family:'Inter',sans-serif;">Mark all read</button>
            @endif
        </div>

        @if($this->recent->isEmpty())
            <p style="font-size:12px;color:#52525b;text-align:center;padding:1.5rem 0;margin:0;">No notifications yet.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach($this->recent as $notification)
                    <a href="{{ route('users.show', $notification->data['follower_id']) }}"
                       style="display:block;padding:8px 10px;border-radius:8px;text-decoration:none;{{ is_null($notification->read_at) ? 'background:rgba(241,198,46,0.06);border-left:2px solid #f1c62e;' : 'border-left:2px solid transparent;' }}">
                        <p style="font-size:12.5px;color:#d4d4d8;margin:0;">
                            <strong style="color:#f4f4f5;">{{ $notification->data['follower_name'] }}</strong> started following you
                        </p>
                        <p style="font-size:11px;color:#3f3f46;margin:2px 0 0;">{{ $notification->created_at->diffForHumans() }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/NotificationBellTest.php`
Expected: PASS

- [ ] **Step 6: Write the failing layout-integration test**

Add to `tests/Feature/Pulse/PulseTest.php` (add `use App\Livewire\Pulse\NotificationBell;` to its imports):

```php
    public function test_the_dashboard_layout_embeds_the_notification_bell(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertSeeLivewire(NotificationBell::class);
    }
```

Run: `php artisan test tests/Feature/Pulse/PulseTest.php --filter test_the_dashboard_layout_embeds_the_notification_bell`
Expected: FAIL — the layout doesn't embed it yet.

- [ ] **Step 7: Mount it in `layouts/app.blade.php`**

In the `<header class="topbar">` block, change:

```blade
        <a href="{{ route('profile.show') }}" class="topbar-btn" title="Profile">
            <span class="material-symbols-rounded">account_circle</span>
        </a>
```

to:

```blade
        @auth
            <livewire:pulse.notification-bell />
        @endauth
        <a href="{{ route('profile.show') }}" class="topbar-btn" title="Profile">
            <span class="material-symbols-rounded">account_circle</span>
        </a>
```

- [ ] **Step 8: Run test to verify it passes**

Run: `php artisan test tests/Feature/Pulse/PulseTest.php`
Expected: PASS (all tests in the file)

- [ ] **Step 9: Commit**

```bash
git add app/Livewire/Pulse/NotificationBell.php resources/views/livewire/pulse/notification-bell.blade.php resources/views/layouts/app.blade.php tests/Feature/Pulse/NotificationBellTest.php tests/Feature/Pulse/PulseTest.php
git commit -m "feat: add the notification bell to the topbar"
```

---

## Task 12: Realtime wiring + manual verification

Ties `NotificationBell` (Task 11) to the `user.{userId}` channel (Task 3) so a follow shows up live, without a page reload — no automated test for this (this codebase has no JS test runner, and every prior real-time feature here — the `pulse-feed`/`post.{id}` channels — was verified manually via the browser pane rather than an automated test). This final task's deliverable is a working, observed end-to-end flow, not a new assertion.

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/js/pulse-realtime.js`

**Interfaces:**
- Consumes: the `user.{userId}` channel (Task 3), `NewFollower::broadcastAs()` = `'notification.new'` (Task 4), `NotificationBell`'s `notification-received` listener (Task 11).

- [ ] **Step 1: Add `data-user-id` to the layout**

In `resources/views/layouts/app.blade.php`, change:

```blade
<body>
```

to:

```blade
<body data-user-id="{{ auth()->id() }}">
```

(`auth()->id()` renders as an empty string for a guest, which `pulse-realtime.js`'s `if (userId)` check below correctly treats as falsy — no channel subscription attempt for logged-out visitors, matching how `[data-post-id]` already works as an opt-in marker.)

- [ ] **Step 2: Add the Echo listener to `pulse-realtime.js`**

Update the file's top docblock to describe three subscriptions instead of two, and add the new one after the existing `post.{id}` block:

```js
    const postId = document.querySelector('[data-post-id]')?.dataset.postId;

    if (postId) {
        window.Echo.private(`post.${postId}`).listen('.comment.posted', () => {
            window.Livewire.dispatch('comment-posted');
        });
    }

    // Private per-user channel, subscribed on every authenticated page
    // (NotificationBell is mounted in the shared topbar, not one specific
    // page) -- unlike post.{id} above, this one is never a no-op
    // subscription. App\Notifications\NewFollower broadcasts here.
    const userId = document.body.dataset.userId;

    if (userId) {
        window.Echo.private(`user.${userId}`).listen('.notification.new', () => {
            window.Livewire.dispatch('notification-received');
        });
    }
}
```

(This replaces the closing `}` of `initPulseRealtime()` — the new block goes immediately before it, after the existing `post.{id}` block.)

- [ ] **Step 3: Manual two-tab verification**

This is the same verification shape already used earlier this session to confirm the Alpine duplicate-instance fix — two authenticated browser tabs, watching a live update happen in one while acting in the other:

1. Start the preview server and create two throwaway users via `php artisan tinker` (e.g. `throwaway-a@example.com` / `throwaway-b@example.com`).
2. Open two browser tabs, log `throwaway-a` into one and `throwaway-b` into the other (`/user/profile` or `/dashboard` on each, confirming both are authenticated).
3. In tab A, navigate to `throwaway-b`'s profile (`/u/{id}`) and click **Follow**.
4. In tab B — *without reloading* — confirm the topbar bell's unread badge appears/increments within a second or two, and opening the bell shows "**throwaway-a's name** started following you".
5. Check `read_console_messages` in tab B for any Echo/Alpine errors during this — expect none.
6. Click **Mark all read** in tab B's bell dropdown; confirm the badge clears.
7. Clean up: delete both throwaway users (and their personal teams/profiles) via `tinker`, matching the cleanup pattern used earlier this session.

- [ ] **Step 4: Run the full test suite once more as a final regression check**

Run: `php artisan test`
Expected: PASS (every test in the suite, not just this feature's own files)

- [ ] **Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php resources/js/pulse-realtime.js
git commit -m "feat: live-update the notification bell over the user.{id} Reverb channel"
```
