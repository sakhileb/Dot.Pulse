<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PostReacted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $reactor,
        public readonly string $emoji,
        public readonly string $targetType, // 'post' or 'comment'
        public readonly int    $targetId,
        public readonly int    $postId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'reaction',
            'message'     => "{$this->reactor->name} reacted {$this->emoji} to your {$this->targetType}",
            'actor_name'  => $this->reactor->name,
            'actor_id'    => $this->reactor->id,
            'post_id'     => $this->postId,
            'url'         => route('posts.show', $this->postId),
        ];
    }
}
