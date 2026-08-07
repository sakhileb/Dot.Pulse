<?php

namespace App\Actions\Pulse;

use App\Models\PulseEvent;
use Carbon\Carbon;

class CreateEvent
{
    public function handle(
        int $userId,
        string $title,
        string $description,
        string $type,
        string $startsAt,
        string $endsAt,
        ?string $url = null,
        ?int $communityId = null,
    ): PulseEvent {
        return PulseEvent::create([
            'user_id' => $userId,
            'community_id' => $communityId,
            'title' => $title,
            'description' => $description ?: null,
            'type' => $type,
            'url' => $url ?: null,
            'starts_at' => Carbon::parse($startsAt),
            'ends_at' => Carbon::parse($endsAt),
            'rsvps_count' => 0,
        ]);
    }
}
