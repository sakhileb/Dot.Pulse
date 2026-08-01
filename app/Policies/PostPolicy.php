<?php

namespace App\Policies;

use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\User;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, PulsePost $post): bool
    {
        // Owner and moderators can always view (moderation queue, drafts, etc.)
        if ($user && ($post->user_id === $user->id || $this->isModerator($user))) {
            return true;
        }

        if ($post->status !== 'published') {
            return false;
        }

        // Posts in a private/enterprise community are only visible to members.
        $community = $post->community;
        if ($community && $community->visibility !== 'public') {
            return $user !== null && $community->hasMember($user);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PulsePost $post): bool
    {
        return $post->user_id === $user->id;
    }

    public function delete(User $user, PulsePost $post): bool
    {
        return $post->user_id === $user->id || $this->isModerator($user);
    }

    public function pin(User $user, PulsePost $post): bool
    {
        return $this->isModerator($user);
    }

    private function isModerator(User $user): bool
    {
        $profile = PulseProfile::where('user_id', $user->id)->first();
        return $profile && in_array($profile->role, ['moderator', 'admin']);
    }
}
