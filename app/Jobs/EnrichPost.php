<?php

namespace App\Jobs;

use App\Services\AiModerationService;
use App\Models\PulsePost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public array $backoff = [10, 30, 60];

    public function __construct(public readonly PulsePost $post) {}

    public function handle(): void
    {
        // Don't re-process if already enriched and published
        if ($this->post->status === 'published' && $this->post->enrichment()->exists()) {
            return;
        }

        $service = new AiModerationService(
            apiKey: config('services.anthropic.key', env('ANTHROPIC_API_KEY', '')),
        );

        $service->enrichPost($this->post);
    }

    public function failed(\Throwable $e): void
    {
        // Fail safe: if AI enrichment fails permanently, never auto-publish.
        // Route the post to human moderation instead of letting it go live
        // unmoderated. It stays out of scopeFeed()/published() results either
        // way, so this only affects whether it's queued for manual review.
        if ($this->post->status === 'pending') {
            $this->post->update(['status' => 'flagged']);
        }
    }
}
