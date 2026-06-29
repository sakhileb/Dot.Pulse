<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PulseHashtag extends Model
{
    protected $table = 'pulse_hashtags';

    protected $fillable = ['name', 'posts_count'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(PulsePost::class, 'pulse_post_hashtag', 'pulse_hashtag_id', 'pulse_post_id');
    }
}
