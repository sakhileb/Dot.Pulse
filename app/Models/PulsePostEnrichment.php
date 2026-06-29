<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PulsePostEnrichment extends Model
{
    protected $table = 'pulse_post_enrichments';

    protected $fillable = [
        'pulse_post_id', 'summary', 'tags', 'sentiment', 'topics', 'keywords',
        'language', 'duplicate_score', 'spam_score', 'safety_score',
        'business_relevance', 'community_score', 'moderation_status', 'moderation_rationale',
    ];

    protected $casts = [
        'tags'               => 'array',
        'topics'             => 'array',
        'keywords'           => 'array',
        'duplicate_score'    => 'float',
        'spam_score'         => 'float',
        'safety_score'       => 'float',
        'business_relevance' => 'float',
        'community_score'    => 'float',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(PulsePost::class, 'pulse_post_id');
    }

    public function isApproved(): bool
    {
        return $this->moderation_status === 'approved';
    }

    public function isFlagged(): bool
    {
        return $this->moderation_status === 'flagged';
    }
}
