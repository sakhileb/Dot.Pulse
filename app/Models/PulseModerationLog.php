<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PulseModerationLog extends Model
{
    protected $table = 'pulse_moderation_logs';

    protected $fillable = [
        'moderator_id', 'target_type', 'target_id', 'action', 'rationale', 'is_ai_decision',
    ];

    protected $casts = [
        'is_ai_decision' => 'boolean',
    ];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
