<?php

namespace App\Livewire\Pulse;

use App\Actions\Pulse\AddComment;
use App\Actions\Pulse\MarkSolution;
use App\Actions\Pulse\ReportContent;
use App\Actions\Pulse\VotePoll;
use App\Models\PulseComment;
use App\Models\PulsePoll;
use App\Models\PulsePost;
use App\Models\PulseReaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PostDetail extends Component
{
    public PulsePost $post;

    public string $commentBody  = '';
    public ?int   $replyingTo   = null;
    public string $replyBody    = '';

    // Reporting
    public bool   $showReport   = false;
    public string $reportReason = '';
    public string $reportDetails= '';

    public function mount(PulsePost $post): void
    {
        $this->post = $post->load(['author', 'community', 'enrichment', 'hashtags']);
    }

    #[Computed]
    public function comments(): Collection
    {
        return PulseComment::with(['author', 'replies.author'])
            ->where('pulse_post_id', $this->post->id)
            ->whereNull('parent_id')
            ->latest()
            ->get();
    }

    #[Computed]
    public function poll(): ?PulsePoll
    {
        if ($this->post->type !== 'poll') {
            return null;
        }

        return PulsePoll::where('pulse_post_id', $this->post->id)->first();
    }

    #[Computed]
    public function pollResults(): array
    {
        if (! $this->poll) {
            return [];
        }

        return app(VotePoll::class)->getResults($this->poll);
    }

    #[Computed]
    public function userVote(): ?int
    {
        if (! $this->poll) {
            return null;
        }

        return app(VotePoll::class)->userVote($this->poll->id, auth()->id());
    }

    // Echo: live comment updates
    #[On('echo:post.{post.id},CommentPosted')]
    public function refreshComments(): void
    {
        unset($this->comments);
        $this->post->refresh();
    }

    public function addComment(): void
    {
        $this->validate(['commentBody' => 'required|min:2|max:5000']);

        app(AddComment::class)->handle(
            postId: $this->post->id,
            userId: auth()->id(),
            body:   $this->commentBody,
        );

        $this->commentBody = '';
        unset($this->comments);
        $this->post->refresh();
    }

    public function addReply(): void
    {
        $this->validate(['replyBody' => 'required|min:2|max:5000']);

        app(AddComment::class)->handle(
            postId:   $this->post->id,
            userId:   auth()->id(),
            body:     $this->replyBody,
            parentId: $this->replyingTo,
        );

        $this->replyBody  = '';
        $this->replyingTo = null;
        unset($this->comments);
        $this->post->refresh();
    }

    public function react(string $morphType, int $morphId, string $emoji = '👍'): void
    {
        if (! RateLimiter::attempt('pulse-reaction:' . auth()->id(), 30, fn () => true)) {
            return;
        }

        $user = auth()->user();

        $existing = PulseReaction::where('user_id', $user->id)
            ->where('reactable_type', $morphType)
            ->where('reactable_id', $morphId)
            ->first();

        if ($existing) {
            $existing->delete();
            if ($morphType === PulsePost::class) {
                $this->post->decrement('reactions_count');
            } else {
                PulseComment::find($morphId)?->decrement('reactions_count');
            }
        } else {
            PulseReaction::create([
                'user_id'        => $user->id,
                'reactable_type' => $morphType,
                'reactable_id'   => $morphId,
                'emoji'          => $emoji,
            ]);
            if ($morphType === PulsePost::class) {
                $this->post->increment('reactions_count');
            } else {
                PulseComment::find($morphId)?->increment('reactions_count');
            }
        }

        $this->post->refresh();
        unset($this->comments);
    }

    public function votePoll(int $optionIndex): void
    {
        if (! $this->poll) {
            return;
        }

        app(VotePoll::class)->handle($this->poll->id, auth()->id(), $optionIndex);
        unset($this->poll, $this->pollResults, $this->userVote);
    }

    public function markSolution(int $commentId): void
    {
        app(MarkSolution::class)->handle($commentId, auth()->id());
        unset($this->comments);
    }

    public function setReplyingTo(?int $commentId): void
    {
        $this->replyingTo = $commentId;
        $this->replyBody  = '';
    }

    public function submitReport(): void
    {
        $this->validate([
            'reportReason' => 'required|min:3|max:100',
        ]);

        app(ReportContent::class)->handle(
            reporterId: auth()->id(),
            morphType:  PulsePost::class,
            morphId:    $this->post->id,
            reason:     $this->reportReason,
            details:    $this->reportDetails,
        );

        $this->showReport   = false;
        $this->reportReason = '';
        $this->reportDetails= '';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.post-detail');
    }
}
