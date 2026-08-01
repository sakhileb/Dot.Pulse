<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PulsePost extends Model
{
    use HasFactory;
    protected $table = 'pulse_posts';

    protected $fillable = [
        'user_id', 'community_id', 'team_id', 'type', 'title', 'body',
        'status', 'is_pinned', 'views_count', 'comments_count', 'reactions_count', 'ai_relevance_score',
    ];

    protected $casts = [
        'is_pinned'          => 'boolean',
        'ai_relevance_score' => 'float',
    ];

    public static array $types = [
        'discussion', 'announcement', 'question', 'idea', 'bug_report', 'release',
        'success_story', 'showcase', 'tutorial', 'agent', 'integration',
        'event', 'article', 'poll', 'video', 'job', 'marketplace',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function enrichment(): HasOne
    {
        return $this->hasOne(PulsePostEnrichment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PulseComment::class)->whereNull('parent_id')->latest();
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(PulseComment::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(PulseReaction::class, 'reactable');
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(PulseHashtag::class, 'pulse_post_hashtag', 'pulse_post_id', 'pulse_hashtag_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeFeed(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->published()->orderByDesc('ai_relevance_score')->orderByDesc('created_at');
    }

    /**
     * Restrict results to posts whose community is public, or a private/enterprise
     * community the given user is a member of. Prevents private-community posts
     * from leaking into the global feed / API for non-members.
     */
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, ?User $user): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($user) {
            $q->whereDoesntHave('community', fn ($c) => $c->where('visibility', '!=', 'public'));

            if ($user) {
                $q->orWhereHas('community', function ($c) use ($user) {
                    $c->where('visibility', '!=', 'public')
                        ->whereHas('memberships', fn ($m) => $m->where('user_id', $user->id));
                });
            }
        });
    }
}
