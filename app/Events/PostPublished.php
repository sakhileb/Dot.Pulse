<?php

namespace App\Events;

use App\Models\PulsePost;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Live feed updates -- README advertises "real-time feed updates via
 * Laravel Reverb" but laravel/reverb was only ever a composer dependency
 * (no config/broadcasting.php, no config/reverb.php, no
 * routes/channels.php) before this pass. Fired the moment a post actually
 * becomes visible in the public feed -- from AiModerationService's
 * auto-approve path and ModerationQueue::approve() (see both call sites).
 *
 * Public channel, not private: HomeFeed's own query has no team or
 * community visibility filter today (it lists every published() post
 * platform-wide) -- a private per-team channel here would impose a
 * privacy boundary the HTTP feed itself doesn't have. broadcastWith() is
 * deliberately minimal: only what a feed card needs to render, matching
 * what HomeFeed's own listing already exposes.
 */
class PostPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PulsePost $post) {}

    public function broadcastOn(): array
    {
        return [new Channel('pulse-feed')];
    }

    public function broadcastAs(): string
    {
        return 'post.published';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->post->id,
            'title' => $this->post->title,
            'type' => $this->post->type,
            'author_name' => $this->post->author->name,
            'community_name' => $this->post->community?->name,
        ];
    }
}
