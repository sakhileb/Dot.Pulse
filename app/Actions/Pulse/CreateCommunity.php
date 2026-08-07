<?php

namespace App\Actions\Pulse;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Support\Str;

class CreateCommunity
{
    public function handle(
        int $userId,
        string $name,
        string $description = '',
        string $industry = '',
        string $visibility = 'public',
        ?int $teamId = null,
    ): Community {
        $slug = $this->uniqueSlug($name);

        $community = Community::create([
            'created_by' => $userId,
            'team_id' => $teamId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description ?: null,
            'industry' => $industry ?: null,
            'visibility' => $visibility,
            'members_count' => 1,
        ]);

        // Creator automatically becomes admin member
        CommunityMembership::create([
            'community_id' => $community->id,
            'user_id' => $userId,
            'role' => 'admin',
        ]);

        return $community;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Community::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
