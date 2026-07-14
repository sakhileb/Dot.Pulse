<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BadgeAwarder;
use Illuminate\Console\Command;

class AwardBadges extends Command
{
    protected $signature   = 'pulse:badges';
    protected $description = 'Run badge eligibility checks for all active users.';

    public function handle(BadgeAwarder $awarder): int
    {
        $this->info('Checking badge eligibility for all users…');

        $awarded = 0;
        User::with('pulseProfile')->chunk(100, function ($users) use ($awarder, &$awarded) {
            foreach ($users as $user) {
                if ($user->pulseProfile) {
                    $awarder->checkAll($user);
                    $awarded++;
                }
            }
        });

        $this->info("Processed {$awarded} users.");

        return self::SUCCESS;
    }
}
