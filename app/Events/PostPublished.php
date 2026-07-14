<?php

namespace App\Events;

use App\Models\PulsePost;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly PulsePost $post) {}

    public function broadcastOn(): array
    {
        $channels = [new Channel('feed')];

        if ($this->post->community_id) {
            $channels[] = new Channel("community.{$this->post->community_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'PostPublished';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->post->id,
            'type'         => $this->post->type,
            'title'        => $this->post->title,
            'community_id' => $this->post->community_id,
            'author'       => $this->post->author->name,
        ];
    }
}
