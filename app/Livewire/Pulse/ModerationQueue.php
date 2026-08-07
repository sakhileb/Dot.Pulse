<?php

namespace App\Livewire\Pulse;

use App\Models\PulseModerationLog;
use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;
use App\Models\PulseProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ModerationQueue extends Component
{
    public string $filter = 'pending';

    #[Computed]
    public function items(): Collection
    {
        return PulsePostEnrichment::with(['post.author', 'post.community'])
            ->when(
                $this->filter === 'pending',
                fn ($q) => $q->where('moderation_status', 'pending'),
            )
            ->when(
                $this->filter === 'flagged',
                fn ($q) => $q->where('moderation_status', 'flagged'),
            )
            ->when(
                $this->filter === 'all',
                fn ($q) => $q->whereIn('moderation_status', ['pending', 'flagged']),
            )
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }

    public function approve(int $enrichmentId): void
    {
        $this->authorizeModeratorAccess();

        $enrichment = PulsePostEnrichment::findOrFail($enrichmentId);
        $enrichment->update(['moderation_status' => 'approved']);
        $enrichment->post->update(['status' => 'published']);

        PulseModerationLog::create([
            'moderator_id' => Auth::id(),
            'target_type' => PulsePost::class,
            'target_id' => $enrichment->pulse_post_id,
            'action' => 'approve',
            'rationale' => 'Manually approved via moderation queue.',
            'is_ai_decision' => false,
        ]);

        unset($this->items);
    }

    public function reject(int $enrichmentId, string $rationale = ''): void
    {
        $this->authorizeModeratorAccess();

        $enrichment = PulsePostEnrichment::findOrFail($enrichmentId);
        $enrichment->update(['moderation_status' => 'rejected']);
        $enrichment->post->update(['status' => 'removed']);

        PulseModerationLog::create([
            'moderator_id' => Auth::id(),
            'target_type' => PulsePost::class,
            'target_id' => $enrichment->pulse_post_id,
            'action' => 'reject',
            'rationale' => $rationale ?: 'Rejected via moderation queue.',
            'is_ai_decision' => false,
        ]);

        unset($this->items);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        unset($this->items);
    }

    private function authorizeModeratorAccess(): void
    {
        $profile = PulseProfile::where('user_id', Auth::id())->first();

        abort_unless(
            $profile && in_array($profile->role, ['moderator', 'admin']),
            403,
        );
    }

    public function render(): View
    {
        return view('livewire.pulse.moderation-queue');
    }
}
