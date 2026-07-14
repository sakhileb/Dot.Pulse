<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PulseReview extends Model
{
    protected $table = 'pulse_reviews';

    protected $fillable = [
        'user_id', 'reviewable_type', 'reviewable_id',
        'rating', 'body', 'is_verified', 'fraud_score',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'fraud_score' => 'float',
        'rating'      => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
