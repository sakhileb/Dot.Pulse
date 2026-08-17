<?php

namespace App\Livewire\Pulse;

use App\Events\PostPublished;
use App\Models\PulseModerationLog;
use App\Models\PulsePost;
use App\Services\HashtagExtractionService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ModerationQueue extends Component
{
    public ?int $rejectingPostId = null;

    public string $rejectReason = '';

    public function mount(): void
    {
        $this->authorize('moderate', PulsePost::class);
    }

    #[Computed]
    public function heldPosts(): Collection
    {
        return PulsePost::where('status', 'flagged')
            ->with(['enrichment', 'author', 'community'])
            ->latest()
            ->get();
    }

    public function approve(int $postId): void
    {
        $this->authorize('moderate', PulsePost::class);

        $post = PulsePost::find($postId);

        if (! $post || $post->status !== 'flagged') {
            return;
        }

        $post->update(['status' => 'published']);

        PulseModerationLog::create([
            'moderator_id' => auth()->id(),
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => 'approve',
            'rationale' => null,
            'is_ai_decision' => false,
        ]);

        (new HashtagExtractionService)->extractAndAttach($post);

        // ShouldBroadcast, not ShouldQueue -- the approval already persisted
        // above, so a broadcast connection failure (Reverb down, network
        // blip) is a lost real-time notice, not a lost moderation decision.
        // Don't 500 the moderator's approve click over it.
        try {
            PostPublished::dispatch($post);
        } catch (\Throwable $e) {
            report($e);
        }

        unset($this->heldPosts);
    }

    public function promptReject(int $postId): void
    {
        $this->authorize('moderate', PulsePost::class);

        $this->rejectingPostId = $postId;
        $this->rejectReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingPostId = null;
        $this->rejectReason = '';
    }

    public function confirmReject(int $postId): void
    {
        $this->authorize('moderate', PulsePost::class);

        if (trim($this->rejectReason) === '') {
            return;
        }

        $post = PulsePost::find($postId);

        if (! $post || $post->status !== 'flagged') {
            return;
        }

        $post->update(['status' => 'removed']);

        PulseModerationLog::create([
            'moderator_id' => auth()->id(),
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => 'reject',
            'rationale' => $this->rejectReason,
            'is_ai_decision' => false,
        ]);

        $this->rejectingPostId = null;
        $this->rejectReason = '';
        unset($this->heldPosts);
    }

    public function render(): View
    {
        return view('livewire.pulse.moderation-queue');
    }
}
