<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PulsePostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can review AI-held posts in the
     * moderation queue (approve or reject). Backed by PulseProfile.role --
     * a real column that already carries 'moderator'/'admin' values but
     * was checked by no authorization code anywhere before this policy.
     */
    public function moderate(User $user): bool
    {
        return in_array($user->pulseProfile?->role, ['moderator', 'admin'], true);
    }
}
