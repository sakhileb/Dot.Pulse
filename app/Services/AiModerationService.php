<?php

namespace App\Services;

use App\Events\PostPublished;
use App\Models\PulseModerationLog;
use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;

class AiModerationService
{
    public function __construct(
        private readonly string $apiKey = '',
        private readonly bool $mock = false,
    ) {}

    /**
     * Enrich and moderate a post. Creates or updates PulsePostEnrichment.
     */
    public function enrichPost(PulsePost $post): PulsePostEnrichment
    {
        if ($this->mock || empty($this->apiKey)) {
            return $this->mockEnrichment($post);
        }

        return $this->callClaude($post);
    }

    private function callClaude(PulsePost $post): PulsePostEnrichment
    {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: '.$this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => 512,
                'messages' => [[
                    'role' => 'user',
                    'content' => $this->buildPrompt($post),
                ]],
            ]),
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || ! $body) {
            return $this->mockEnrichment($post);
        }

        $data = json_decode($body, true);
        $text = $data['content'][0]['text'] ?? '{}';

        preg_match('/\{.*\}/s', $text, $matches);
        $parsed = json_decode($matches[0] ?? '{}', true) ?? [];

        return $this->upsertEnrichment($post, $parsed);
    }

    private function buildPrompt(PulsePost $post): string
    {
        $content = $post->title ? "{$post->title}\n\n{$post->body}" : $post->body;

        return <<<PROMPT
You are the AI moderation pipeline for Dot.Pulse, a professional business community platform.

Analyse this post and return JSON only, no markdown:
{
  "summary": "2-sentence summary",
  "tags": ["tag1", "tag2"],
  "sentiment": "positive|neutral|negative|mixed",
  "topics": ["topic1"],
  "keywords": ["kw1"],
  "language": "en",
  "spam_score": 0.0,
  "safety_score": 1.0,
  "business_relevance": 0.8,
  "community_score": 0.7,
  "moderation_status": "approved|flagged|rejected",
  "moderation_rationale": "brief rationale if flagged/rejected, else null"
}

Post type: {$post->type}
Post content:
{$content}
PROMPT;
    }

    private function mockEnrichment(PulsePost $post): PulsePostEnrichment
    {
        return $this->upsertEnrichment($post, [
            'summary' => 'A community post shared on Dot.Pulse.',
            'tags' => ['community', $post->type],
            'sentiment' => 'positive',
            'topics' => ['business', 'community'],
            'keywords' => [],
            'language' => 'en',
            'spam_score' => 0.0,
            'safety_score' => 1.0,
            'business_relevance' => 0.7,
            'community_score' => 0.6,
            'moderation_status' => 'approved',
            'moderation_rationale' => null,
        ]);
    }

    public function upsertEnrichment(PulsePost $post, array $data): PulsePostEnrichment
    {
        $enrichment = PulsePostEnrichment::updateOrCreate(
            ['pulse_post_id' => $post->id],
            [
                'summary' => $data['summary'] ?? null,
                'tags' => $data['tags'] ?? [],
                'sentiment' => $data['sentiment'] ?? 'neutral',
                'topics' => $data['topics'] ?? [],
                'keywords' => $data['keywords'] ?? [],
                'language' => $data['language'] ?? 'en',
                'spam_score' => $data['spam_score'] ?? 0.0,
                'safety_score' => $data['safety_score'] ?? 1.0,
                'business_relevance' => $data['business_relevance'] ?? 0.5,
                'community_score' => $data['community_score'] ?? 0.5,
                'moderation_status' => $data['moderation_status'] ?? 'approved',
                'moderation_rationale' => $data['moderation_rationale'] ?? null,
            ]
        );

        if ($enrichment->moderation_status === 'approved' && $post->status === 'pending') {
            $post->update(['status' => 'published']);
            $this->logAiDecision($post, 'approve', $enrichment->moderation_rationale);
            (new HashtagExtractionService)->extractAndAttach($post);

            // ShouldBroadcast, not ShouldQueue -- the post is already published
            // above, so a broadcast failure here (Reverb down, network blip)
            // shouldn't 500 a brand-new post out from under the author who just
            // published it.
            try {
                PostPublished::dispatch($post);
            } catch (\Throwable $e) {
                report($e);
            }
        } elseif (in_array($enrichment->moderation_status, ['rejected', 'flagged'], true)) {
            // Both a rejected classification and an unsure ("flagged") one now hold the
            // post for human review instead of taking effect immediately -- 'rejected' no
            // longer auto-removes, and 'flagged' no longer silently does nothing.
            // status: 'flagged' is the pre-existing enum value that was earmarked for
            // exactly this held-for-review state but never actually set by any code.
            $post->update(['status' => 'flagged']);
            $this->logAiDecision(
                $post,
                $enrichment->moderation_status === 'rejected' ? 'reject' : 'flag',
                $enrichment->moderation_rationale,
            );
        }

        return $enrichment;
    }

    private function logAiDecision(PulsePost $post, string $action, ?string $rationale): void
    {
        PulseModerationLog::create([
            'moderator_id' => null,
            'target_type' => PulsePost::class,
            'target_id' => $post->id,
            'action' => $action,
            'rationale' => $rationale,
            'is_ai_decision' => true,
        ]);
    }
}
