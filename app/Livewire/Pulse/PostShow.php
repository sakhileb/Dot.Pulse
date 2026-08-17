<?php

namespace App\Livewire\Pulse;

use App\Events\CommentPosted;
use App\Models\PulseComment;
use App\Models\PulsePost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Threaded comments — pulse_comments (parent_id self-reference, is_solution,
 * reactions_count) has existed since the platform's first migration with
 * zero UI or controller reading/writing it; README advertises "threaded
 * comments on every post" but nothing let a user post, reply to, react to,
 * or mark one as a solution. This is also the first page a post gets a
 * permanent URL of its own, since the feed only ever rendered inline cards.
 */
class PostShow extends Component
{
    public PulsePost $post;

    public string $newCommentBody = '';

    public ?int $replyingToId = null;

    public string $replyBody = '';

    public function mount(PulsePost $post): void
    {
        // The feed only ever lists published() posts; a held/removed post
        // has no card linking here, but its URL is still guessable. Keep
        // that same boundary here rather than letting a direct link leak
        // a moderation-held post to anyone but its own author.
        abort_unless($post->isPublished() || $post->user_id === auth()->id(), 404);

        $this->post = $post;
    }

    #[Computed]
    public function comments(): Collection
    {
        return $this->post->comments()
            ->with(['author.pulseProfile', 'replies.author.pulseProfile'])
            ->oldest()
            ->get();
    }

    /**
     * resources/js/pulse-realtime.js dispatches this on a live
     * App\Events\CommentPosted broadcast — mirrors HomeFeed's own
     * #[On('post-created')] pattern.
     */
    #[On('comment-posted')]
    public function refreshComments(): void
    {
        unset($this->comments);
    }

    public function postComment(): void
    {
        $this->validate(['newCommentBody' => 'required|string|min:1|max:5000']);

        $comment = $this->post->comments()->create([
            'user_id' => auth()->id(),
            'body' => $this->newCommentBody,
        ]);

        $this->post->increment('comments_count');
        $this->broadcastSafely(fn () => CommentPosted::dispatch($comment));
        $this->reset('newCommentBody');
        unset($this->comments);
    }

    public function startReply(int $commentId): void
    {
        $this->replyingToId = $commentId;
        $this->replyBody = '';
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->replyBody = '';
    }

    public function postReply(int $parentId): void
    {
        $this->validate(['replyBody' => 'required|string|min:1|max:5000']);

        $parent = PulseComment::where('pulse_post_id', $this->post->id)->findOrFail($parentId);

        $reply = $parent->replies()->create([
            'pulse_post_id' => $this->post->id,
            'user_id' => auth()->id(),
            'body' => $this->replyBody,
        ]);

        $this->post->increment('comments_count');
        $this->broadcastSafely(fn () => CommentPosted::dispatch($reply));
        $this->cancelReply();
        unset($this->comments);
    }

    /**
     * ShouldBroadcast (not ShouldQueue) means the comment/reply is already
     * durably saved by the time this runs -- the broadcast is a best-effort
     * real-time notice to other open tabs, not the source of truth. A
     * transient WebSocket/Pusher-protocol connection failure (Reverb not
     * running, network blip) shouldn't 500 the request and roll the user
     * back to believing their comment never posted when it did.
     */
    private function broadcastSafely(\Closure $dispatch): void
    {
        try {
            $dispatch();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Same toggle-react shape as HomeFeed::react() (pulse_reactions has a
     * unique (user_id, reactable_type, reactable_id) constraint — one
     * reaction per user per thing, not one per emoji).
     */
    public function react(int $commentId, string $emoji = '👍'): void
    {
        $comment = PulseComment::where('pulse_post_id', $this->post->id)->findOrFail($commentId);
        $existing = $comment->reactions()->where('user_id', auth()->id())->first();

        if ($existing) {
            $existing->delete();
            $comment->decrement('reactions_count');
        } else {
            $comment->reactions()->create(['user_id' => auth()->id(), 'emoji' => $emoji]);
            $comment->increment('reactions_count');
        }

        unset($this->comments);
    }

    /**
     * Only the post's own author marks a solution (Stack-Overflow-style
     * "OP accepts the answer"), regardless of post type — a type-based
     * gate (question/bug_report only) would be more precise but also more
     * fragile than it's worth for a first pass. At most one solution per
     * post: marking a new one unmarks the previous. First real call site
     * for PulseProfile::addPoints()/solutions_accepted, both of which
     * existed unused before this.
     */
    public function markSolution(int $commentId): void
    {
        abort_unless($this->post->user_id === auth()->id(), 403);

        $comment = PulseComment::where('pulse_post_id', $this->post->id)->findOrFail($commentId);

        if ($comment->is_solution) {
            return;
        }

        PulseComment::where('pulse_post_id', $this->post->id)
            ->where('is_solution', true)
            ->update(['is_solution' => false]);

        $comment->update(['is_solution' => true]);

        $profile = $comment->author->pulseProfile;
        if ($profile) {
            $profile->addPoints(10);
            $profile->increment('solutions_accepted');
        }

        unset($this->comments);
    }

    public function render(): View
    {
        return view('livewire.pulse.post-show');
    }
}
