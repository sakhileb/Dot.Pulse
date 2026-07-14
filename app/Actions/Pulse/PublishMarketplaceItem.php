<?php

namespace App\Actions\Pulse;

use App\Models\PulseMarketplaceItem;

class PublishMarketplaceItem
{
    public function handle(
        int    $userId,
        string $title,
        string $description,
        string $category,
        string $version = '1.0.0',
    ): PulseMarketplaceItem {
        return PulseMarketplaceItem::create([
            'user_id'     => $userId,
            'title'       => $title,
            'description' => $description ?: null,
            'category'    => $category,
            'version'     => $version,
            'is_published'=> false, // requires review first
        ]);
    }
}
