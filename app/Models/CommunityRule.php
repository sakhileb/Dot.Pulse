<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityRule extends Model
{
    protected $fillable = ['community_id', 'title', 'description', 'sort_order'];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
