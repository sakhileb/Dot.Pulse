<?php

namespace App\Actions\Pulse;

use App\Models\PulseFollower;
use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Support\Facades\RateLimiter;

class FollowUser
{
    /**
     * Toggle follow/unfollow. Returns true if now following, false if unfollowed.
     */
    public function handle(User $follower, User $following): bool
    {
        if ($follower->id === $following->id) {
            return false;
        }

        // Rate limiting
        abort_unless(
            RateLimiter::attempt('pulse-follow:'.$follower->id, 20, fn () => true),
            429,
            'Too many follow actions. Please slow down.',
        );

        $existing = PulseFollower::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        PulseFollower::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        // Notify the followed user
        $following->notify(new NewFollower($follower));

        return true;
    }

    public function isFollowing(User $follower, User $following): bool
    {
        return PulseFollower::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->exists();
    }
}
