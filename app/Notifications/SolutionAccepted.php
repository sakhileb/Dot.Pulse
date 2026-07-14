<?php

namespace App\Notifications;

use App\Models\PulseComment;
use App\Models\PulsePost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SolutionAccepted extends Notification implements ShouldQueue
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
            'type'       => 'solution',
            'message'    => "Your answer was accepted as the solution on \"{$this->post->title}\"",
            'post_id'    => $this->post->id,
            'post_title' => $this->post->title ?? Str::limit($this->post->body, 60),
            'comment_id' => $this->comment->id,
            'url'        => route('posts.show', $this->post->id) . '#comment-' . $this->comment->id,
        ];
    }
}
