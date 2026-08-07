<?php

namespace App\Actions\Pulse;

use App\Events\MessageSent;
use App\Models\PulseConversation;
use App\Models\PulseMessage;

class SendMessage
{
    public function handle(
        int $conversationId,
        int $senderId,
        string $body,
    ): PulseMessage {
        // Ensure sender is a participant
        $conversation = PulseConversation::findOrFail($conversationId);

        abort_unless(
            $conversation->participants()->where('user_id', $senderId)->exists(),
            403,
            'You are not a participant of this conversation.',
        );

        $message = PulseMessage::create([
            'pulse_conversation_id' => $conversationId,
            'user_id' => $senderId,
            'body' => $body,
        ]);

        MessageSent::dispatch($message->load('sender'));

        return $message;
    }
}
