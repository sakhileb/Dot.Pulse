<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Community extends Model
{
    protected $fillable = [
        'team_id', 'created_by', 'name', 'slug', 'description',
        'avatar_url', 'banner_url', 'industry', 'visibility', 'members_count',
    ];

    protected $casts = [
        'members_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CommunityMembership::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CommunityRule::class)->orderBy('sort_order');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(PulsePost::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->exists();
    }
}
