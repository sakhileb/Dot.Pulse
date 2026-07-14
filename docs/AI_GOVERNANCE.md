# Dot.Pulse AI Governance Policy
Version: 1.0 | Claude + OpenAI | Classification: Internal — Confidential

---

## Purpose

This document governs how Dot.Pulse designs, deploys, monitors, and improves all AI-powered features. It applies to every AI model interaction — including content moderation, knowledge extraction, summarisation, recommendation, and fraud detection.

---

## AI Features Inventory

| Feature | Model | Mode | Human Review? |
|---|---|---|---|
| Post content moderation | Claude (primary) | Async (queued) | Flagged/borderline cases |
| Post enrichment (tags, sentiment, summary) | Claude | Async (queued) | No |
| Spam/toxicity scoring | Claude | Async (queued) | Score > 0.7 |
| Knowledge graph extraction | Claude | Async (queued) | No |
| Duplicate detection | Claude | Async (queued) | No |
| Smart feed ranking | In-house scoring | Sync | No |
| User AI assistant (planned) | Claude / GPT-4 | On-demand | Low-confidence outputs |
| Translation (planned) | Claude / DeepL | On-demand | No |
| Fraud detection (planned) | Fine-tuned model | Async | Score > 0.8 |

---

## Model Selection Policy

| Task | Primary Model | Fallback | Rationale |
|---|---|---|---|
| Content moderation | Claude Sonnet 4.5 | Mock (rule-based) | Balance of quality and cost |
| Complex reasoning | Claude Opus | Claude Sonnet | Quality-first for critical decisions |
| Fast classification | Claude Haiku | Rule engine | Speed + cost for high-volume |
| Image moderation | Claude Vision | Manual queue | Multimodal capability |
| Code generation (planned) | Claude Sonnet | GitHub Copilot API | Ecosystem integration |

### Selection Criteria

1. **Safety first** — Choose models with the strongest safety alignment for user-facing outputs
2. **Cost efficiency** — Match model complexity to task complexity
3. **Fallback reliability** — Every AI feature must degrade gracefully to a rule-based fallback
4. **Latency budget** — Moderation must complete within 30 seconds of post creation

---

## Prompt Engineering Standards

### Prompt Structure

Every system prompt must include:

```
1. Platform context (what Dot.Pulse is)
2. Task definition (exactly what the model should do)
3. Output format specification (strict JSON schema)
4. Boundary conditions (what to do when uncertain)
5. Safety instructions (what to refuse)
```

### Template (Moderation Prompt)

```
You are the AI moderation pipeline for Dot.Pulse, a professional business 
community platform for the Dot ecosystem.

Your task: Analyse the submitted post and return a JSON object conforming 
exactly to the schema below. Do not add commentary outside the JSON.

Context:
- This platform is used by businesses, developers, and enterprise customers
- Users discuss: AI agents, automation, fleet management, ERP, mining, agriculture
- The platform is designed for professional business discourse

Output schema:
{
  "summary": "string: 1-2 sentence neutral summary",
  "tags": ["array", "of", "lowercase", "strings"],
  "sentiment": "positive|neutral|negative|mixed",
  "topics": ["topic1", "topic2"],
  "keywords": ["keyword1", "keyword2"],
  "language": "ISO 639-1 language code",
  "spam_score": "float 0.0-1.0 (0=not spam, 1=definitely spam)",
  "safety_score": "float 0.0-1.0 (1=completely safe, 0=harmful)",
  "business_relevance": "float 0.0-1.0 (1=highly relevant to business)",
  "community_score": "float 0.0-1.0 (1=excellent community content)",
  "moderation_status": "approved|flagged|rejected",
  "moderation_rationale": "string|null: reason if flagged or rejected"
}

Approve if: business relevant, not harmful, not spam
Flag if: borderline, uncertain, requires human review
Reject if: harmful, toxic, scam, fraud, explicit NSFW

Post type: {type}
Post content:
{content}
```

### Prompt Injection Prevention

User input is **never interpolated directly** into system prompts. Only specific, validated fields are included:

```php
// ✅ Controlled interpolation
$prompt = str_replace(
    ['{type}', '{content}'],
    [
        e($post->type),                             // Escaped
        mb_substr(strip_tags($post->body), 0, 2000) // Stripped and truncated
    ],
    $template,
);

// ❌ Direct interpolation — injection risk
$prompt = "Analyse this post: {$post->body}";
```

---

## AI Output Validation

Every AI response must be validated before acting on it:

```php
private function validateResponse(array $parsed): bool
{
    // Required fields present
    if (! isset($parsed['moderation_status'], $parsed['safety_score'])) {
        return false;
    }

    // Status is a known value
    if (! in_array($parsed['moderation_status'], ['approved', 'flagged', 'rejected'])) {
        return false;
    }

    // Scores are within expected range
    foreach (['spam_score', 'safety_score', 'business_relevance'] as $score) {
        if (! isset($parsed[$score]) || $parsed[$score] < 0 || $parsed[$score] > 1) {
            return false;
        }
    }

    return true;
}
```

If validation fails → use mock fallback → log warning → route to human review queue.

---

## Fallback Strategy

The AI pipeline must never block content creation. Failure modes:

| Failure | Response |
|---|---|
| API timeout (> 30s) | Use mock enrichment, auto-approve, log warning |
| API error (4xx/5xx) | Retry once, then mock fallback |
| Invalid JSON response | Parse best-effort, flag for human review |
| All retries exhausted | Job `failed()` → auto-publish with `needs_review` flag |
| Daily API limit exceeded | Alert engineering, switch to rule-based fallback |

The `EnrichPost` job has:
- 3 retry attempts
- Exponential backoff: 10s, 30s, 60s
- `failed()` handler auto-publishes after permanent failure

---

## Human Review Queue

Content is routed to the moderation queue when:

| Condition | Threshold |
|---|---|
| `spam_score` | > 0.6 |
| `safety_score` | < 0.5 |
| `moderation_status` | `flagged` |
| AI confidence low | < 0.4 (when confidence scoring is added) |
| User report received | Any |
| Account under review | Any post |

Moderators act via the `/moderation` dashboard (ModerationQueue Livewire component).

---

## AI Usage Logging

Every AI API call must be logged with:

```php
// Logged to pulse_moderation_logs (extended) or dedicated ai_logs table
[
    'call_id'        => Str::uuid(),
    'model'          => 'claude-sonnet-4-5',
    'input_tokens'   => $response['usage']['input_tokens'],
    'output_tokens'  => $response['usage']['output_tokens'],
    'latency_ms'     => $latency,
    'success'        => true,
    'target_type'    => PulsePost::class,
    'target_id'      => $post->id,
    'is_ai_decision' => true,
    'rationale'      => $enrichment->moderation_rationale,
]
```

This log is used for:
- Cost tracking and budgeting
- Performance monitoring
- Model accuracy auditing
- Compliance evidence (who made what decision, when)

---

## Cost Management

| Model | Cost Policy |
|---|---|
| Claude Sonnet | Max 2,000 input tokens per moderation call |
| Claude Opus | Reserved for escalated reviews only |
| Claude Haiku | For high-volume classification (future) |

Set a monthly budget alert at 80% of allocated AI spend. At 100%, switch to mock fallback and alert engineering.

Track per-feature spend:
- `moderation` — highest volume
- `knowledge_graph` — medium volume
- `user_assistant` (future) — variable

---

## AI Safety Controls

### Pre-Request Controls

- [ ] User is authenticated
- [ ] Content passes basic spam filter (length, character composition)
- [ ] User not flagged as bot or under suspension
- [ ] Rate limit not exceeded
- [ ] Prompt injection patterns not detected in input

### Post-Response Controls

- [ ] Response JSON is valid and schema-conformant
- [ ] Scores are within expected ranges
- [ ] Status is a known value
- [ ] No PII echoed back in AI response
- [ ] Response logged before acting on it

### Forbidden AI Behaviours

The platform must never:
- Use AI to auto-ban users without human review
- Use AI to generate content on behalf of users without explicit consent
- Pass raw user passwords, tokens, or private messages to external AI APIs
- Use AI decisions as the sole basis for legal or account termination decisions

---

## Quarterly AI Review Checklist

- [ ] Review false positive rate (approved content later reported)
- [ ] Review false negative rate (harmful content that passed moderation)
- [ ] Audit prompt templates for outdated context
- [ ] Verify model version is current (not deprecated)
- [ ] Review AI cost trends vs. budget
- [ ] Check for model drift (changing behaviour over time)
- [ ] Review human review queue resolution time
- [ ] Update knowledge graph accuracy metrics
- [ ] Review rate of fallback invocations (rising = problem signal)

---

## Future AI Roadmap

| Feature | Quarter | Notes |
|---|---|---|
| User AI assistant | Q3 2026 | "Summarise today's discussions" |
| Recommendation engine | Q3 2026 | Community/expert/post recommendations |
| Trend analyst agent | Q4 2026 | Emerging topics, industry insights |
| Knowledge curator agent | Q4 2026 | Auto-generate docs from solved discussions |
| Translation agent | Q1 2027 | Real-time multilingual content |
| Fraud detection model | Q1 2027 | Fine-tuned on platform data |
| Customer success agent | Q2 2027 | Route unanswered questions |
