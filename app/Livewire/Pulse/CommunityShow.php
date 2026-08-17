<?php

namespace App\Livewire\Pulse;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The first drill-down page for a community — until now a community was only
 * ever a row in the sidebar list (CommunityList), with its name in a post
 * card not even a link. Community::posts() and hasMember() existed with no
 * page to call them from.
 */
class CommunityShow extends Component
{
    public Community $community;

    public function mount(Community $community): void
    {
        $this->community = $community;
    }

    #[Computed]
    public function posts(): Collection
    {
        return $this->community->posts()
            ->published()
            ->with(['author', 'enrichment'])
            ->latest()
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function isMember(): bool
    {
        return auth()->check() && $this->community->hasMember(auth()->user());
    }

    public function join(): void
    {
        if ($this->isMember) {
            return;
        }

        CommunityMembership::create([
            'community_id' => $this->community->id,
            'user_id' => auth()->id(),
            'role' => 'member',
        ]);

        $this->community->increment('members_count');
        unset($this->isMember);
    }

    public function leave(): void
    {
        CommunityMembership::where('community_id', $this->community->id)
            ->where('user_id', auth()->id())
            ->delete();

        $this->community->decrement('members_count');
        unset($this->isMember);
    }

    public function render(): View
    {
        return view('livewire.pulse.community-show');
    }
}
