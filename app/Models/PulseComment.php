<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PulseComment extends Model
{
    protected $table = 'pulse_comments';

    protected $fillable = [
        'pulse_post_id', 'user_id', 'parent_id', 'body', 'is_solution', 'reactions_count',
    ];

    protected $casts = [
        'is_solution' => 'boolean',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(PulsePost::class, 'pulse_post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->latest();
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(PulseReaction::class, 'reactable');
    }
}
