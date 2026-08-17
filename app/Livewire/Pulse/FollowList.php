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
