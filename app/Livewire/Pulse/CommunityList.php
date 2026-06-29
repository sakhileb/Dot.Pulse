<?php

namespace App\Livewire\Pulse;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CommunityList extends Component
{
    public string $search = '';

    #[Computed]
    public function communities(): Collection
    {
        return Community::withCount('memberships')
            ->where('visibility', 'public')
            ->when($this->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('industry', 'like', '%' . $this->search . '%')
            ))
            ->orderByDesc('members_count')
            ->limit(24)
            ->get();
    }

    public function join(int $communityId): void
    {
        $community = Community::findOrFail($communityId);

        if ($community->hasMember(auth()->user())) {
            return;
        }

        CommunityMembership::create([
            'community_id' => $communityId,
            'user_id'      => auth()->id(),
            'role'         => 'member',
        ]);

        $community->increment('members_count');
        unset($this->communities);
    }

    public function leave(int $communityId): void
    {
        $community = Community::findOrFail($communityId);

        CommunityMembership::where('community_id', $communityId)
            ->where('user_id', auth()->id())
            ->delete();

        $community->decrement('members_count');
        unset($this->communities);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.community-list');
    }
}
