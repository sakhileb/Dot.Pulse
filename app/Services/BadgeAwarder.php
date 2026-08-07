<?php

namespace App\Services;

use App\Models\PulseBadge;
use App\Models\PulseProfile;
use App\Models\PulseUserBadge;
use App\Models\User;

class BadgeAwarder
{
    /**
     * Check and award all badges a user is eligible for.
     * Called after any action that might unlock a badge.
     */
    public function checkAll(User $user): void
    {
        $profile = PulseProfile::where('user_id', $user->id)->first();
        if (! $profile) {
            return;
        }

        $this->checkFirstPost($user, $profile);
        $this->checkSolutionGiver($user, $profile);
        $this->checkMentor($user, $profile);
        $this->checkAiBuilder($user, $profile);
    }

    public function award(User $user, string $badgeKey): bool
    {
        $badge = PulseBadge::where('key', $badgeKey)->first();
        if (! $badge) {
            return false;
        }

        $already = PulseUserBadge::where('user_id', $user->id)
            ->where('pulse_badge_id', $badge->id)
            ->exists();

        if ($already) {
            return false;
        }

        PulseUserBadge::create([
            'user_id' => $user->id,
            'pulse_badge_id' => $badge->id,
            'awarded_at' => now(),
        ]);

        return true;
    }

    private function checkFirstPost(User $user, PulseProfile $profile): void
    {
        if ($user->posts()->where('status', 'published')->exists()) {
            $this->award($user, 'first_post');
        }
    }

    private function checkSolutionGiver(User $user, PulseProfile $profile): void
    {
        if ($profile->solutions_accepted >= 1) {
            $this->award($user, 'solution_giver');
        }
    }

    private function checkMentor(User $user, PulseProfile $profile): void
    {
        if ($profile->solutions_accepted >= 10) {
            $this->award($user, 'mentor');
        }
    }

    private function checkAiBuilder(User $user, PulseProfile $profile): void
    {
        if ($user->posts()->whereIn('type', ['agent', 'automation', 'integration'])->where('status', 'published')->count() >= 3) {
            $this->award($user, 'ai_builder');
        }
    }
}
