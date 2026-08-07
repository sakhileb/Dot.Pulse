<?php

namespace App\Notifications;

use App\Models\PulseComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PulseComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'mention',
            'message' => "{$this->comment->author->name} mentioned you in a comment",
            'post_id' => $this->comment->pulse_post_id,
            'comment_id' => $this->comment->id,
            'actor_name' => $this->comment->author->name,
            'url' => route('posts.show', $this->comment->pulse_post_id).'#comment-'.$this->comment->id,
        ];
    }
}
