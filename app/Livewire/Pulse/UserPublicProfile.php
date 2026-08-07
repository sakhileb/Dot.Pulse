<?php

namespace App\Livewire\Pulse;

use App\Actions\Pulse\FollowUser as FollowUserAction;
use App\Models\PulseConversation;
use App\Models\PulseFollower;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\PulseUserBadge;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class UserPublicProfile extends Component
{
    public User $user;

    public PulseProfile $profile;

    public string $tab = 'posts';

    public function mount(User $user, PulseProfile $profile): void
    {
        $this->user = $user;
        $this->profile = $profile;
    }

    #[Computed]
    public function posts(): Collection
    {
        return PulsePost::with(['community', 'enrichment'])
            ->published()
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function badges(): Collection
    {
        return PulseUserBadge::with('badge')
            ->where('user_id', $this->user->id)
            ->latest('awarded_at')
            ->get();
    }

    #[Computed]
    public function followersCount(): int
    {
        return PulseFollower::where('following_id', $this->user->id)->count();
    }

    #[Computed]
    public function followingCount(): int
    {
        return PulseFollower::where('follower_id', $this->user->id)->count();
    }

    #[Computed]
    public function isFollowing(): bool
    {
        if (Auth::id() === $this->user->id) {
            return false;
        }

        return PulseFollower::where('follower_id', Auth::id())
            ->where('following_id', $this->user->id)
            ->exists();
    }

    #[Computed]
    public function isOwnProfile(): bool
    {
        return Auth::id() === $this->user->id;
    }

    public function toggleFollow(): void
    {
        if ($this->isOwnProfile) {
            return;
        }

        app(FollowUserAction::class)->handle(Auth::user(), $this->user);
        unset($this->isFollowing, $this->followersCount);
    }

    public function startConversation(): void
    {
        $conversation = PulseConversation::directBetween(Auth::id(), $this->user->id);
        $this->redirect(route('messages.show', $conversation->id), navigate: true);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render(): View
    {
        return view('livewire.pulse.user-public-profile');
    }
}
