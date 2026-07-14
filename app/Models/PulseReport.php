<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PulseReport extends Model
{
    protected $table = 'pulse_reports';

    protected $fillable = [
        'reporter_id', 'reportable_type', 'reportable_id',
        'reason', 'details', 'status',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public static array $reasons = [
        'spam', 'harassment', 'misinformation', 'hate_speech',
        'nsfw', 'scam', 'fake_review', 'off_topic', 'other',
    ];
}
