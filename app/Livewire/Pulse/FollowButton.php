<?php

namespace App\Livewire\Pulse;

use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The single source of truth for follow/unfollow -- embedded, compact,
 * next to any author's name (feed, post detail, comments, follower/
 * following lists, suggested users) as well as full-size on a profile
 * page. See docs/superpowers/specs/
 * 2026-08-17-follow-graph-polish-and-notifications.md.
 */
class FollowButton extends Component
{
    public User $user;

    public bool $compact = false;

    #[Computed]
    public function isFollowing(): bool
    {
        return auth()->check() && auth()->user()->isFollowing($this->user);
    }

    #[Computed]
    public function isSelf(): bool
    {
        return auth()->id() === $this->user->id;
    }

    public function toggleFollow(): void
    {
        abort_if($this->isSelf, 422, 'You cannot follow yourself.');

        $viewer = auth()->user();

        if ($viewer->isFollowing($this->user)) {
            $viewer->following()->detach($this->user->id);
        } else {
            $viewer->following()->attach($this->user->id);
            $this->notifySafely();
        }

        unset($this->isFollowing);
        $this->dispatch('follow-toggled', userId: $this->user->id);
    }

    /**
     * Mirrors PostShow::broadcastSafely() exactly -- NewFollower's
     * broadcast leg over Reverb must not roll back the pulse_followers
     * row that already committed above it. Not separately unit-tested
     * for the failure path (same as the three existing call sites this
     * mirrors) -- a Mockery partial mock on $this->user wouldn't survive
     * Livewire's snapshot/hydrate cycle between test calls, so this is
     * verified by code review plus the manual two-tab pass at the end of
     * the realtime-wiring task, consistent with how this codebase already
     * treats this category of resilience.
     */
    private function notifySafely(): void
    {
        try {
            $this->user->notify(new NewFollower(auth()->user()));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render(): View
    {
        return view('livewire.pulse.follow-button');
    }
}
