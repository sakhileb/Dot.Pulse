<?php

namespace App\Services;

use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;

class AiModerationService
{
    public function __construct(
        private readonly string $apiKey = '',
        private readonly bool   $mock   = false,
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
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 512,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => $this->buildPrompt($post),
                ]],
            ]),
        ]);

        $body     = curl_exec($ch);
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
            'summary'              => 'A community post shared on Dot.Pulse.',
            'tags'                 => ['community', $post->type],
            'sentiment'            => 'positive',
            'topics'               => ['business', 'community'],
            'keywords'             => [],
            'language'             => 'en',
            'spam_score'           => 0.0,
            'safety_score'         => 1.0,
            'business_relevance'   => 0.7,
            'community_score'      => 0.6,
            'moderation_status'    => 'approved',
            'moderation_rationale' => null,
        ]);
    }

    private function upsertEnrichment(PulsePost $post, array $data): PulsePostEnrichment
    {
        $enrichment = PulsePostEnrichment::updateOrCreate(
            ['pulse_post_id' => $post->id],
            [
                'summary'              => $data['summary'] ?? null,
                'tags'                 => $data['tags'] ?? [],
                'sentiment'            => $data['sentiment'] ?? 'neutral',
                'topics'               => $data['topics'] ?? [],
                'keywords'             => $data['keywords'] ?? [],
                'language'             => $data['language'] ?? 'en',
                'spam_score'           => $data['spam_score'] ?? 0.0,
                'safety_score'         => $data['safety_score'] ?? 1.0,
                'business_relevance'   => $data['business_relevance'] ?? 0.5,
                'community_score'      => $data['community_score'] ?? 0.5,
                'moderation_status'    => $data['moderation_status'] ?? 'approved',
                'moderation_rationale' => $data['moderation_rationale'] ?? null,
            ]
        );

        if ($enrichment->moderation_status === 'approved' && $post->status === 'pending') {
            $post->update(['status' => 'published']);
        } elseif ($enrichment->moderation_status === 'rejected') {
            $post->update(['status' => 'removed']);
        }

        return $enrichment;
    }
}
