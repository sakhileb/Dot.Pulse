<?php

namespace App\Notifications;

use App\Models\PulseComment;
use App\Models\PulsePost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewCommentOnPost extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PulseComment $comment,
        public readonly PulsePost $post,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'comment',
            'message'    => "{$this->comment->author->name} commented on your post",
            'post_id'    => $this->post->id,
            'post_title' => $this->post->title ?? Str::limit($this->post->body, 60),
            'comment_id' => $this->comment->id,
            'actor_name' => $this->comment->author->name,
            'url'        => route('posts.show', $this->post->id),
        ];
    }
}
