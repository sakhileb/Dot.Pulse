<?php

namespace App\Events;

use App\Models\PulseComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CommentPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly PulseComment $comment) {}

    public function broadcastOn(): array
    {
        return [new Channel("post.{$this->comment->pulse_post_id}")];
    }

    public function broadcastAs(): string
    {
        return 'CommentPosted';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->comment->id,
            'pulse_post_id'  => $this->comment->pulse_post_id,
            'parent_id'      => $this->comment->parent_id,
            'author'         => $this->comment->author->name,
            'body_preview'   => Str::limit($this->comment->body, 80),
        ];
    }
}
