<?php

namespace App\Events;

use App\Models\PulseMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly PulseMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("conversation.{$this->message->pulse_conversation_id}")];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->pulse_conversation_id,
            'sender_name'     => $this->message->sender->name,
            'body'            => $this->message->body,
            'created_at'      => $this->message->created_at->toIso8601String(),
        ];
    }
}
