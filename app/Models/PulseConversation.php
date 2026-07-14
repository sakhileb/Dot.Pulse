<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PulseConversation extends Model
{
    protected $table = 'pulse_conversations';

    protected $fillable = ['type', 'name'];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pulse_conversation_user')
            ->withPivot('last_read_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PulseMessage::class)->oldest();
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(PulseMessage::class)->latestOfMany();
    }

    /**
     * Find or create a direct conversation between two users.
     */
    public static function directBetween(int $userAId, int $userBId): self
    {
        // Find existing direct conversation shared by both users
        $conversation = self::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userAId))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userBId))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        $conversation = self::create(['type' => 'direct']);
        $conversation->participants()->attach([$userAId, $userBId]);

        return $conversation;
    }
}
