<?php

use App\Models\PulseConversation;
use Illuminate\Support\Facades\Broadcast;

// Private channel: conversation participants only
Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    return PulseConversation::where('id', $conversationId)
        ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
        ->exists();
});

// Private channel: each user's personal notification channel
Broadcast::channel('App.Models.User.{id}', function ($user, int $id) {
    return (int) $user->id === $id;
});
