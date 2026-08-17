/**
 * Live feed + comment delivery — README advertises "real-time feed updates
 * via Laravel Reverb" but nothing broadcast or listened for anything
 * before this pass (see App\Events\PostPublished / CommentPosted and
 * App\Providers\BroadcastServiceProvider).
 *
 * Three independent subscriptions:
 *  - pulse-feed (public): any authenticated page can receive it; only
 *    HomeFeed's own #[On('post-created')] listener does anything with it,
 *    so subscribing on a page without that component mounted is a no-op,
 *    not a bug (see docs/DOT_REALTIME_STANDARD.md §7's advice to
 *    initialize from a shell mounted on every authenticated page).
 *  - post.{id} (private): only subscribed when a [data-post-id] element
 *    is present (PostShow's own page) — this is a per-post channel, not
 *    something every page should join.
 *  - user.{id} (private): subscribed on every authenticated page, since
 *    NotificationBell is mounted in the shared topbar layout, not one
 *    specific page. App\Notifications\NewFollower broadcasts here.
 */
function initPulseRealtime() {
    if (typeof window.Echo === 'undefined') {
        console.error('Laravel Echo not found. Ensure bootstrap.js loaded before pulse-realtime.js.');
        return;
    }

    window.Echo.channel('pulse-feed').listen('.post.published', () => {
        // HomeFeed's own #[On('post-created')] handler already busts its
        // #[Computed] posts() cache and re-renders — the exact mechanism
        // CreatePost::publish() dispatches after the viewer's own post.
        window.Livewire.dispatch('post-created');
    });

    const postId = document.querySelector('[data-post-id]')?.dataset.postId;

    if (postId) {
        window.Echo.private(`post.${postId}`).listen('.comment.posted', () => {
            // PostShow's own #[On('comment-posted')] handler busts its
            // #[Computed] comments() cache, mirroring HomeFeed's pattern.
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

if (typeof window.Livewire === 'undefined') {
    document.addEventListener('livewire:init', initPulseRealtime);
} else {
    initPulseRealtime();
}
