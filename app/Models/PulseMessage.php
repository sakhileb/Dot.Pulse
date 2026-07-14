<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PulseMessage extends Model
{
    protected $table = 'pulse_messages';

    protected $fillable = ['pulse_conversation_id', 'user_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PulseConversation::class, 'pulse_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
