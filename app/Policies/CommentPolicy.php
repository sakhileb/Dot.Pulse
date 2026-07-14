<?php

namespace App\Policies;

use App\Models\PulseComment;
use App\Models\PulseProfile;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, PulseComment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }

        // Post author can delete comments on their post
        if ($comment->post->user_id === $user->id) {
            return true;
        }

        return $this->isModerator($user);
    }

    private function isModerator(User $user): bool
    {
        $profile = PulseProfile::where('user_id', $user->id)->first();
        return $profile && in_array($profile->role, ['moderator', 'admin']);
    }
}
