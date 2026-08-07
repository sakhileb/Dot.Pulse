<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewFollower extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $follower) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'follow',
            'message' => "{$this->follower->name} started following you",
            'actor_name' => $this->follower->name,
            'actor_id' => $this->follower->id,
            'url' => route('profile.public', $this->follower->name),
        ];
    }
}
