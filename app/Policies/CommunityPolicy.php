<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\PulseProfile;
use App\Models\User;

class CommunityPolicy
{
    public function view(?User $user, Community $community): bool
    {
        if ($community->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $community->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true; // any authenticated user
    }

    public function update(User $user, Community $community): bool
    {
        return CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin'])
            ->exists();
    }

    public function delete(User $user, Community $community): bool
    {
        return $community->created_by === $user->id
            || $this->isGlobalAdmin($user);
    }

    private function isGlobalAdmin(User $user): bool
    {
        $profile = PulseProfile::where('user_id', $user->id)->first();

        return $profile && $profile->role === 'admin';
    }
}
