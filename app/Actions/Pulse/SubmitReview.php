<?php

namespace App\Actions\Pulse;

use App\Models\PulseReview;

class SubmitReview
{
    public function handle(
        int    $userId,
        string $reviewableType,
        int    $reviewableId,
        int    $rating,
        string $body = '',
    ): PulseReview {
        abort_if($rating < 1 || $rating > 5, 422, 'Rating must be between 1 and 5.');

        // One review per user per reviewable
        return PulseReview::updateOrCreate(
            [
                'user_id'          => $userId,
                'reviewable_type'  => $reviewableType,
                'reviewable_id'    => $reviewableId,
            ],
            [
                'rating'      => $rating,
                'body'        => $body ?: null,
                'is_verified' => false,
                'fraud_score' => 0.0,
            ]
        );
    }
}
