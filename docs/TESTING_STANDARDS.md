# Dot.Pulse Testing Standards
Version: 1.0 | PHPUnit 12 | Classification: Internal

---

## Philosophy

> Tests are documentation that never goes out of date.

Every test must answer: *"What should happen when X does Y?"* — not *"Does this code run?"*

Test coverage is a **floor, not a ceiling**. 95% line coverage with shallow tests is worse than 80% coverage with deep behavioural tests.

---

## Test Suite Structure

```
tests/
├── Unit/                   # Pure function / method tests (no DB, no HTTP)
│   ├── Actions/            # Action class unit tests
│   └── Services/           # Service class unit tests
│
├── Feature/                # Integration tests (real DB, real HTTP stack)
│   ├── Pulse/
│   │   ├── PostTest.php
│   │   ├── CommentTest.php
│   │   ├── CommunityTest.php
│   │   ├── FollowTest.php
│   │   ├── ModerationTest.php
│   │   └── MessagingTest.php
│   └── Api/                # Dedicated API contract tests
│
└── TestCase.php            # Base test class
```

---

## Base Test Class

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // All Pulse tests use RefreshDatabase to reset between runs
    // Use RefreshDatabase in Feature tests, NOT in Unit tests
}
```

---

## Feature Test Template

```php
<?php

namespace Tests\Feature\Pulse;

use App\Models\Community;
use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange: one authenticated user with a profile
        $this->user = User::factory()->withPersonalTeam()->create();
        PulseProfile::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function authenticated_user_can_create_a_post(): void
    {
        Queue::fake();  // Don't actually run jobs

        $this->actingAs($this->user)
            ->postJson('/api/v1/posts', [
                'type' => 'question',
                'body' => 'How do I integrate fleet tracking into Dot.Agents?',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.type', 'question');

        $this->assertDatabaseHas('pulse_posts', [
            'user_id' => $this->user->id,
            'type'    => 'question',
            'status'  => 'pending',
        ]);

        Queue::assertPushed(\App\Jobs\EnrichPost::class);
    }
}
```

---

## What to Test

### For Every Action Class

- Happy path: expected input → expected output + side effects
- Guard conditions: rate limit exceeded → 429
- Policy violations: unauthorized user → 403
- Validation: invalid input → 422 with specific error messages
- Side effects: correct events fired, correct notifications sent, correct points awarded

### For Every API Endpoint

- Authentication: missing token → 401
- Authorization: wrong user → 403
- Validation: invalid body → 422 with field-level errors
- Success: valid input → 201 + correct JSON shape
- Not found: invalid ID → 404
- Conflict: duplicate action → 409
- Rate limit: excess requests → 429

### For Every Policy

- `true` cases: owner can perform action
- `false` cases: non-owner cannot perform action
- Role escalation: moderator can perform actions a regular user cannot
- Null user: guest cannot perform authenticated actions

---

## Testing Notifications

```php
public function test_comment_notifies_post_author(): void
{
    Notification::fake();

    $author    = User::factory()->withPersonalTeam()->create();
    $commenter = User::factory()->withPersonalTeam()->create();
    PulseProfile::factory()->create(['user_id' => $commenter->id]);

    $post = PulsePost::factory()->create(['user_id' => $author->id, 'status' => 'published']);

    $this->actingAs($commenter)
        ->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Great post, very helpful!',
        ])
        ->assertCreated();

    Notification::assertSentTo($author, \App\Notifications\NewCommentOnPost::class);
    Notification::assertNotSentTo($commenter, \App\Notifications\NewCommentOnPost::class);
}
```

---

## Testing Jobs (Queue)

```php
public function test_enrich_post_job_is_dispatched_on_create(): void
{
    Queue::fake();

    $this->actingAs($this->user)
        ->postJson('/api/v1/posts', ['type' => 'discussion', 'body' => 'Test body content'])
        ->assertCreated();

    Queue::assertPushed(\App\Jobs\EnrichPost::class, function ($job) {
        return $job->post->user_id === $this->user->id;
    });
}

public function test_enrich_post_job_publishes_post_on_success(): void
{
    $post = PulsePost::factory()->create(['status' => 'pending']);

    // Run the actual job (not faked)
    (new \App\Jobs\EnrichPost($post))->handle();

    $this->assertDatabaseHas('pulse_posts', ['id' => $post->id, 'status' => 'published']);
}
```

---

## Testing Events

```php
use Illuminate\Support\Facades\Event;

public function test_post_published_event_fires_when_approved(): void
{
    Event::fake([\App\Events\PostPublished::class]);

    // Trigger AI moderation approval
    $post    = PulsePost::factory()->create(['status' => 'pending']);
    $service = new \App\Services\AiModerationService(apiKey: '', mock: true);
    $service->enrichPost($post);

    Event::assertDispatched(\App\Events\PostPublished::class, function ($event) use ($post) {
        return $event->post->id === $post->id;
    });
}
```

---

## Testing Policies

```php
public function test_post_policy_allows_author_to_delete(): void
{
    $post   = PulsePost::factory()->create(['user_id' => $this->user->id]);
    $policy = new \App\Policies\PostPolicy();

    $this->assertTrue($policy->delete($this->user, $post));
}

public function test_post_policy_blocks_non_author(): void
{
    $other  = User::factory()->create();
    $post   = PulsePost::factory()->create(['user_id' => $this->user->id]);
    $policy = new \App\Policies\PostPolicy();

    $this->assertFalse($policy->delete($other, $post));
}
```

---

## Testing Rate Limiting

```php
public function test_post_creation_is_rate_limited(): void
{
    Queue::fake();

    // Make 10 requests (the limit)
    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($this->user)
            ->postJson('/api/v1/posts', ['type' => 'discussion', 'body' => str_repeat('a', 15)])
            ->assertCreated();
    }

    // 11th request should be rejected
    $this->actingAs($this->user)
        ->postJson('/api/v1/posts', ['type' => 'discussion', 'body' => str_repeat('a', 15)])
        ->assertStatus(429);
}
```

---

## Testing Multi-Tenant Isolation

For every resource, verify cross-tenant access is blocked:

```php
public function test_user_cannot_access_another_tenants_post(): void
{
    $tenantA = User::factory()->withPersonalTeam()->create();
    $tenantB = User::factory()->withPersonalTeam()->create();

    // In a multi-tenant setup, post belongs to tenant A's team
    $post = PulsePost::factory()->create([
        'user_id' => $tenantA->id,
        'status'  => 'published',
    ]);

    // Tenant B should not be able to delete tenant A's post
    $this->actingAs($tenantB)
        ->deleteJson("/api/v1/posts/{$post->id}")
        ->assertForbidden();
}
```

---

## Factory Conventions

Every factory must produce realistic data:

```php
class PulsePostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'type'               => $this->faker->randomElement(PulsePost::$types),
            'title'              => $this->faker->sentence(6),
            'body'               => $this->faker->paragraphs(2, true),
            'status'             => 'published',  // Default to published for testing
            'ai_relevance_score' => $this->faker->randomFloat(2, 0.3, 1.0),
        ];
    }

    // State methods for specific scenarios
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function question(): static
    {
        return $this->state(['type' => 'question', 'title' => 'How do I ' . $this->faker->sentence(4) . '?']);
    }
}
```

---

## Test Data Principles

| Principle | Implementation |
|---|---|
| Each test is independent | `RefreshDatabase` runs before each test class |
| Factories produce realistic data | Not `'name' => 'test'` but `fake()->name()` |
| Only create what the test needs | Minimal setUp — extra data masks failures |
| No shared state between tests | No `static` properties or class-level DB state |
| No external API calls in tests | Mock all HTTP clients (`Http::fake()`) |
| No filesystem calls without mocking | `Storage::fake()` for file upload tests |

---

## Assertions Beyond `assertStatus`

```php
// Shape of response data
->assertJsonStructure([
    'data' => [
        'id', 'type', 'body', 'status',
        'author' => ['id', 'name'],
    ],
    'links', 'meta',
])

// Exact value at path
->assertJsonPath('data.status', 'published')
->assertJsonPath('data.author.name', $this->user->name)

// Count items in response
->assertJsonCount(3, 'data')

// Database state
$this->assertDatabaseHas('pulse_posts', ['id' => $post->id, 'status' => 'published']);
$this->assertDatabaseMissing('pulse_posts', ['id' => $post->id]);
$this->assertDatabaseCount('pulse_posts', 5);
$this->assertSoftDeleted('pulse_posts', ['id' => $post->id]);
```

---

## CI Coverage Gate

```bash
./vendor/bin/phpunit --coverage-text --min-coverage=95

# Coverage by directory
./vendor/bin/phpunit --coverage-html coverage/
```

Coverage thresholds enforced in CI:
- Lines: 95%
- Functions/methods: 90%
- Classes: 85%

Coverage is a lagging indicator. Prioritise **meaningful assertions** over raw line coverage.

---

## Pending Tests to Write

| Area | Tests Needed | Priority |
|---|---|---|
| Poll voting | Vote, change vote, closed poll guard | P0 |
| Report content | Create report, duplicate guard, rate limit | P0 |
| Badge awarding | First post, solution giver, mentor | P1 |
| Knowledge graph | Node creation from approved post | P1 |
| File upload | MIME validation, size limit, storage | P1 |
| Marketplace | Publish, install, duplicate install guard | P1 |
| Event creation | Create event, RSVP, past event guard | P1 |
| Notification bell | Unread count, mark read | P2 |
| Search | Posts, communities, users results | P2 |
| Trending topics | Cache hit, cache bust after recalculation | P2 |
