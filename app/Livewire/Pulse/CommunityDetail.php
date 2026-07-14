<?php

namespace App\Livewire\Pulse;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\PulsePost;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CommunityDetail extends Component
{
    public Community $community;
    public string $tab        = 'posts';
    public string $filterType = '';

    public function mount(Community $community): void
    {
        $this->community = $community->load(['creator', 'rules']);
    }

    #[Computed]
    public function posts(): Collection
    {
        return PulsePost::with(['author', 'enrichment'])
            ->published()
            ->where('community_id', $this->community->id)
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function members(): Collection
    {
        return CommunityMembership::with('user')
            ->where('community_id', $this->community->id)
            ->latest()
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function isMember(): bool
    {
        return $this->community->hasMember(auth()->user());
    }

    #[Computed]
    public function memberRole(): ?string
    {
        return CommunityMembership::where('community_id', $this->community->id)
            ->where('user_id', auth()->id())
            ->value('role');
    }

    public function join(): void
    {
        if ($this->isMember) {
            return;
        }

        CommunityMembership::create([
            'community_id' => $this->community->id,
            'user_id'      => auth()->id(),
            'role'         => 'member',
        ]);

        $this->community->increment('members_count');
        $this->community->refresh();
        unset($this->isMember, $this->memberRole);
    }

    public function leave(): void
    {
        // Prevent the last admin from leaving
        $isOnlyAdmin = CommunityMembership::where('community_id', $this->community->id)
            ->where('role', 'admin')
            ->count() === 1
            && $this->memberRole === 'admin';

        if ($isOnlyAdmin) {
            $this->addError('leave', 'You are the only admin. Transfer admin rights before leaving.');
            return;
        }

        CommunityMembership::where('community_id', $this->community->id)
            ->where('user_id', auth()->id())
            ->delete();

        $this->community->decrement('members_count');
        $this->community->refresh();
        unset($this->isMember, $this->memberRole, $this->members);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        unset($this->posts, $this->members);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.community-detail');
    }
}
