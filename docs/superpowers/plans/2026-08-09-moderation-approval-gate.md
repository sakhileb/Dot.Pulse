# AI Content Moderation Approval Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A post Claude classifies as `rejected` or `flagged` no longer auto-removes (or silently gets stuck) — both hold at `status: 'flagged'` and wait for a moderator/admin to approve (publish) or reject (remove, reason required) on a new `/moderation` queue. `approved` still auto-publishes, unchanged.

**Architecture:** No new schema. `pulse_posts.status` already has an unused `'flagged'` enum value that becomes the held-for-review state; `pulse_moderation_logs` already exists, unused, and becomes this gate's audit trail; `PulseProfile.role` already has `moderator`/`admin` values, checked by a new policy for the first time. `AiModerationService::upsertEnrichment()` changes its branching and gets a testability fix (visibility). A new `PulsePostPolicy`, `ModerationQueue` Livewire component, and `/moderation` route provide the human review surface.

**Tech Stack:** Laravel 13, Livewire 3 (confirmed via `composer.lock`: `livewire/livewire` v3.8.2), PHPUnit, Tailwind (existing Livewire component convention — see `create-post.blade.php`).

## Global Constraints

- No migration in this plan. `pulse_posts.status` enum already includes `'flagged'` (`database/migrations/2026_06_29_000001_create_pulse_core_tables.php:82`); `pulse_moderation_logs` already exists (`database/migrations/2026_06_29_000002_create_pulse_extended_tables.php:150`).
- `approved` → still auto-publishes, unchanged. Only `rejected` (no longer auto-removes) and `flagged` (previously a dead end — did nothing) are in scope for this gate.
- Reviewer authority is `PulseProfile.role IN ('moderator', 'admin')` — no new flag, no new table.
- Every decision (AI's and the human's) writes one row to `pulse_moderation_logs`, using its existing columns (`moderator_id`, `target_type`/`target_id` morph, `action`, `rationale`, `is_ai_decision`).
- Policy class must be named `PulsePostPolicy` (not `PostPolicy` as the spec's prose shorthand implied) — Laravel's policy auto-discovery maps `App\Models\PulsePost` → `App\Policies\PulsePostPolicy` by convention; the wrong name means `$this->authorize('moderate', PulsePost::class)` silently fails to resolve. Confirmed against this repo's own working example: `App\Models\Team` → `App\Policies\TeamPolicy`.
- Out of scope (per spec): per-community moderator roles, `pulse_reports`, restoring a `removed` post, pagination/rate-limiting on the queue, any change to the `approved` path or to when `enrichPost()` is triggered.
- Every `git add` lists files explicitly, never `-A`/`.` — this repo had pre-existing unrelated uncommitted changes (`application-mark.blade.php`, mark images) stashed before this work started, on a stale `feature/ecosystem-sso` branch, not part of this work.

---

### Task 1: `AiModerationService` holds instead of auto-removing, logs every AI decision

**Files:**
- Modify: `app/Services/AiModerationService.php`
- Modify: `app/Models/PulseModerationLog.php`
- Modify: `app/Models/PulsePostEnrichment.php`
- Test: `tests/Feature/Pulse/AiModerationServiceTest.php`

**Interfaces:**
- Produces: `AiModerationService::upsertEnrichment(PulsePost $post, array $data): PulsePostEnrichment` (now `public`, was `private`), `PulseModerationLog::create(array $attributes)` with fillable `moderator_id`, `target_type`, `target_id`, `action`, `rationale`, `is_ai_decision`, `PulsePostEnrichment::isRejected(): bool`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Pulse/AiModerationServiceTest.php`:

```php
<?php

namespace Tests\Feature\Pulse;

use App\Models\PulsePost;
use App\Models\User;
use App\Services\AiModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pendingPost(): PulsePost
    {
        $user = User::factory()->withPersonalTeam()->create();

        return PulsePost::create([
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'type'    => 'discussion',
            'body'    => 'A post awaiting moderation.',
            'status'  => 'pending',
        ]);
    }

    public function test_approved_classification_still_auto_publishes(): void
    {
        $post = $this->pendingPost();
        $service = new AiModerationService(mock: true);

        $service->upsertEnrichment($post, [
            'moderation_status'    => 'approved',
            'moderation_rationale' => null,
        ]);

        $this->assertSame('published', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => 'approve',
            'is_ai_decision' => true,
            'moderator_id'   => null,
        ]);
    }

    public function test_rejected_classification_holds_for_review_instead_of_removing(): void
    {
        $post = $this->pendingPost();
        $service = new AiModerationService(mock: true);

        $service->upsertEnrichment($post, [
            'moderation_status'    => 'rejected',
            'moderation_rationale' => 'Looks like spam.',
        ]);

        $this->assertSame('flagged', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => 'reject',
            'rationale'      => 'Looks like spam.',
            'is_ai_decision' => true,
        ]);
    }

    public function test_flagged_classification_now_holds_for_review_instead_of_being_dropped(): void
    {
        $post = $this->pendingPost();
        $service = new AiModerationService(mock: true);

        $service->upsertEnrichment($post, [
            'moderation_status'    => 'flagged',
            'moderation_rationale' => 'Unsure about business relevance.',
        ]);

        $this->assertSame('flagged', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => 'flag',
            'is_ai_decision' => true,
        ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/AiModerationServiceTest.php`
Expected: FAIL — `upsertEnrichment()` is `private` (not callable from the test), `pulse_moderation_logs` never gets written to, `rejected` still sets `status: 'removed'`.

- [ ] **Step 3: Complete the `PulseModerationLog` model**

Replace the full contents of `app/Models/PulseModerationLog.php` (currently a bare stub with only `$table` set):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PulseModerationLog extends Model
{
    protected $table = 'pulse_moderation_logs';

    protected $fillable = [
        'moderator_id', 'target_type', 'target_id', 'action', 'rationale', 'is_ai_decision',
    ];

    protected $casts = [
        'is_ai_decision' => 'boolean',
    ];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
```

- [ ] **Step 4: Add `isRejected()` to `PulsePostEnrichment`**

In `app/Models/PulsePostEnrichment.php`, add after the existing `isFlagged()` method:

```php
    public function isRejected(): bool
    {
        return $this->moderation_status === 'rejected';
    }
```

- [ ] **Step 5: Rewrite `AiModerationService::upsertEnrichment()`**

In `app/Services/AiModerationService.php`, add an import and replace the method:

```php
use App\Models\PulseModerationLog;
```

(add alongside the existing `use App\Models\PulsePost;` and `use App\Models\PulsePostEnrichment;` imports)

Replace the existing `private function upsertEnrichment(...)` method (and everything in it) with:

```php
    public function upsertEnrichment(PulsePost $post, array $data): PulsePostEnrichment
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
            $this->logAiDecision($post, 'approve', $enrichment->moderation_rationale);
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
            'moderator_id'   => null,
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => $action,
            'rationale'      => $rationale,
            'is_ai_decision' => true,
        ]);
    }
```

The only functional change to the method's signature is visibility (`private` → `public`) — a deliberate testability fix. `AiModerationService::callClaude()` talks to Claude over a raw `curl_init()` call, not Laravel's `Http` facade, so there is no `Http::fake()` available to simulate specific classifications end-to-end; making `upsertEnrichment()` public is the minimal change that lets tests exercise the real branching logic (rejected/flagged/approved) directly, without touching the network layer. No other behavior changes.

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/AiModerationServiceTest.php`
Expected: PASS (3 tests)

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 6 if it reformats anything.

- [ ] **Step 8: Commit**

```bash
git add app/Services/AiModerationService.php app/Models/PulseModerationLog.php \
  app/Models/PulsePostEnrichment.php tests/Feature/Pulse/AiModerationServiceTest.php \
  docs/superpowers/plans/2026-08-09-moderation-approval-gate.md
git commit -m "$(cat <<'EOF'
feat: AI moderation holds rejected/flagged posts instead of auto-removing

upsertEnrichment() no longer auto-removes a 'rejected' post -- it now
holds at status: 'flagged' alongside 'flagged' classifications, which
previously did nothing at all (a second, silent dead-end: the post
just stayed 'pending' forever). status: 'flagged' is a pre-existing
enum value that was never actually set by any code before this.

Completes PulseModerationLog (was a bare stub) and writes one log row
per AI decision -- the first code ever to use this table, which has
existed, unused, since the original schema migration.

upsertEnrichment() is now public (was private): AiModerationService
talks to Claude via raw curl, not Http::fake()-able, so exercising the
branching logic directly is the only practical way to test it without
touching the network layer.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: `PulsePostPolicy` + `ModerationQueue` review UI

**Files:**
- Modify: `app/Models/User.php`
- Create: `app/Policies/PulsePostPolicy.php`
- Create: `app/Livewire/Pulse/ModerationQueue.php`
- Create: `resources/views/livewire/pulse/moderation-queue.blade.php`
- Create: `resources/views/pulse/moderation.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Pulse/ModerationQueueTest.php`

**Interfaces:**
- Consumes: `PulseModerationLog` (Task 1), `PulsePostEnrichment::isRejected()`/`isFlagged()` (Task 1 + pre-existing).
- Produces: `User::pulseProfile(): HasOne`, `PulsePostPolicy::moderate(User $user): bool`, `ModerationQueue::approve(int $postId)`, `promptReject(int $postId)`, `cancelReject()`, `confirmReject(int $postId)`, `$rejectingPostId`/`$rejectReason` component state, `$this->heldPosts` computed property.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Pulse/ModerationQueueTest.php`:

```php
<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\ModerationQueue;
use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;
use App\Models\PulseProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModerationQueueTest extends TestCase
{
    use RefreshDatabase;

    private function heldPost(string $moderationStatus = 'rejected'): PulsePost
    {
        $author = User::factory()->withPersonalTeam()->create();
        $post = PulsePost::create([
            'user_id' => $author->id,
            'team_id' => $author->currentTeam->id,
            'type'    => 'discussion',
            'body'    => 'A post awaiting moderation.',
            'status'  => 'flagged',
        ]);
        PulsePostEnrichment::create([
            'pulse_post_id'        => $post->id,
            'moderation_status'    => $moderationStatus,
            'moderation_rationale' => 'Test rationale.',
        ]);

        return $post;
    }

    private function moderator(): User
    {
        $moderator = User::factory()->withPersonalTeam()->create();
        PulseProfile::create(['user_id' => $moderator->id, 'role' => 'moderator']);

        return $moderator;
    }

    public function test_moderator_can_approve_a_held_post(): void
    {
        $post = $this->heldPost();
        $moderator = $this->moderator();

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('approve', $post->id);

        $this->assertSame('published', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => 'approve',
            'is_ai_decision' => false,
            'moderator_id'   => $moderator->id,
        ]);
    }

    public function test_moderator_can_reject_with_a_reason(): void
    {
        $post = $this->heldPost();
        $moderator = $this->moderator();

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('promptReject', $post->id)
            ->set('rejectReason', 'Confirmed spam.')
            ->call('confirmReject', $post->id);

        $this->assertSame('removed', $post->fresh()->status);
        $this->assertDatabaseHas('pulse_moderation_logs', [
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => 'reject',
            'rationale'      => 'Confirmed spam.',
            'is_ai_decision' => false,
            'moderator_id'   => $moderator->id,
        ]);
    }

    public function test_rejecting_without_a_reason_is_a_noop(): void
    {
        $post = $this->heldPost();
        $moderator = $this->moderator();

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('promptReject', $post->id)
            ->set('rejectReason', '')
            ->call('confirmReject', $post->id);

        $this->assertSame('flagged', $post->fresh()->status);
    }

    public function test_acting_on_a_post_no_longer_held_is_a_noop(): void
    {
        $post = $this->heldPost();
        $moderator = $this->moderator();
        $post->update(['status' => 'published']); // already resolved by someone else

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('approve', $post->id);

        $this->assertSame('published', $post->fresh()->status);
        $this->assertDatabaseCount('pulse_moderation_logs', 0);
    }

    public function test_non_moderator_is_blocked(): void
    {
        $this->heldPost();
        $regularUser = User::factory()->withPersonalTeam()->create();
        PulseProfile::create(['user_id' => $regularUser->id, 'role' => 'customer']);

        $this->withoutExceptionHandling();
        $this->expectException(AuthorizationException::class);

        Livewire::actingAs($regularUser)->test(ModerationQueue::class);
    }

    public function test_moderation_route_requires_authentication(): void
    {
        $this->get('/moderation')->assertRedirect('/login');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Pulse/ModerationQueueTest.php`
Expected: FAIL — `App\Livewire\Pulse\ModerationQueue` doesn't exist yet, `/moderation` route doesn't exist yet.

- [ ] **Step 3: Add `User::pulseProfile()`**

In `app/Models/User.php`, add the import:

```php
use Illuminate\Database\Eloquent\Relations\HasOne;
```

And add this method inside the class (after the `casts()` method):

```php
    public function pulseProfile(): HasOne
    {
        return $this->hasOne(PulseProfile::class);
    }
```

- [ ] **Step 4: Create `PulsePostPolicy`**

Create `app/Policies/PulsePostPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\PulsePost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PulsePostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can review AI-held posts in the
     * moderation queue (approve or reject). Backed by PulseProfile.role --
     * a real column that already carries 'moderator'/'admin' values but
     * was checked by no authorization code anywhere before this policy.
     */
    public function moderate(User $user): bool
    {
        return in_array($user->pulseProfile?->role, ['moderator', 'admin'], true);
    }
}
```

Note: this class must be named `PulsePostPolicy`, matching `App\Models\PulsePost` under Laravel's policy auto-discovery convention (mirrors this repo's own `App\Models\Team` → `App\Policies\TeamPolicy`) — not `PostPolicy`, which would not be auto-discovered for the `PulsePost` model.

- [ ] **Step 5: Create the `ModerationQueue` Livewire component**

Create `app/Livewire/Pulse/ModerationQueue.php`:

```php
<?php

namespace App\Livewire\Pulse;

use App\Models\PulseModerationLog;
use App\Models\PulsePost;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ModerationQueue extends Component
{
    public ?int $rejectingPostId = null;

    public string $rejectReason = '';

    public function mount(): void
    {
        $this->authorize('moderate', PulsePost::class);
    }

    #[Computed]
    public function heldPosts(): Collection
    {
        return PulsePost::where('status', 'flagged')
            ->with(['enrichment', 'author', 'community'])
            ->latest()
            ->get();
    }

    public function approve(int $postId): void
    {
        $this->authorize('moderate', PulsePost::class);

        $post = PulsePost::find($postId);

        if (! $post || $post->status !== 'flagged') {
            return;
        }

        $post->update(['status' => 'published']);

        PulseModerationLog::create([
            'moderator_id'   => auth()->id(),
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => 'approve',
            'rationale'      => null,
            'is_ai_decision' => false,
        ]);

        unset($this->heldPosts);
    }

    public function promptReject(int $postId): void
    {
        $this->authorize('moderate', PulsePost::class);

        $this->rejectingPostId = $postId;
        $this->rejectReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingPostId = null;
        $this->rejectReason = '';
    }

    public function confirmReject(int $postId): void
    {
        $this->authorize('moderate', PulsePost::class);

        if (trim($this->rejectReason) === '') {
            return;
        }

        $post = PulsePost::find($postId);

        if (! $post || $post->status !== 'flagged') {
            return;
        }

        $post->update(['status' => 'removed']);

        PulseModerationLog::create([
            'moderator_id'   => auth()->id(),
            'target_type'    => PulsePost::class,
            'target_id'      => $post->id,
            'action'         => 'reject',
            'rationale'      => $this->rejectReason,
            'is_ai_decision' => false,
        ]);

        $this->rejectingPostId = null;
        $this->rejectReason = '';
        unset($this->heldPosts);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.moderation-queue');
    }
}
```

- [ ] **Step 6: Create the component view**

Create `resources/views/livewire/pulse/moderation-queue.blade.php`:

```blade
<div class="max-w-3xl">
    @if($this->heldPosts->isEmpty())
        <div class="bg-white rounded-xl shadow p-5 text-sm text-gray-400">
            Nothing waiting for review.
        </div>
    @endif

    @foreach($this->heldPosts as $post)
        <div class="bg-white rounded-xl shadow p-5 mb-4">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $post->author->name }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $post->community?->name ?? 'No community' }} &middot; {{ ucfirst($post->type) }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $post->enrichment?->isRejected() ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $post->enrichment?->isRejected() ? 'AI recommended reject' : 'AI flagged for review' }}
                </span>
            </div>

            @if($post->title)
                <p class="text-sm font-semibold text-gray-800 mb-1">{{ $post->title }}</p>
            @endif
            <p class="text-sm text-gray-600 mb-3">{{ $post->body }}</p>

            @if($post->enrichment)
                <div class="text-xs text-gray-400 mb-3 space-y-1">
                    @if($post->enrichment->moderation_rationale)
                        <p>AI rationale: {{ $post->enrichment->moderation_rationale }}</p>
                    @endif
                    <p>Spam score: {{ number_format($post->enrichment->spam_score, 2) }} &middot; Safety score: {{ number_format($post->enrichment->safety_score, 2) }}</p>
                </div>
            @endif

            @if($rejectingPostId === $post->id)
                <div class="flex items-center gap-2">
                    <input type="text" wire:model="rejectReason" placeholder="Reason for rejecting"
                        class="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
                    <button wire:click="confirmReject({{ $post->id }})" class="text-xs px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white font-medium">
                        Confirm Reject
                    </button>
                    <button wire:click="cancelReject" class="text-xs px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium">
                        Cancel
                    </button>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <button wire:click="approve({{ $post->id }})" class="text-xs px-3 py-1.5 rounded bg-green-600 hover:bg-green-700 text-white font-medium">
                        Approve
                    </button>
                    <button wire:click="promptReject({{ $post->id }})" class="text-xs px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white font-medium">
                        Reject
                    </button>
                </div>
            @endif
        </div>
    @endforeach
</div>
```

- [ ] **Step 7: Create the page view**

Create `resources/views/pulse/moderation.blade.php`:

```blade
<x-app-layout>

<div style="padding:2rem 2.5rem 3rem;max-width:1100px;">

    <div style="margin-bottom:2rem;">
        <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:#f4f4f5;margin:0 0 0.2rem;letter-spacing:-0.01em;">Moderation Queue</h1>
        <p style="font-size:0.78rem;color:#52525b;margin:0;">Posts AI flagged or recommended rejecting, awaiting your review</p>
    </div>

    <livewire:pulse.moderation-queue />

</div>

</x-app-layout>
```

- [ ] **Step 8: Add the route**

In `routes/web.php`, add inside the existing `auth:sanctum` / `jetstream.auth_session` / `verified` group, after the `/dashboard` route's closing `});`:

```php
    Route::get('/moderation', fn () => view('pulse.moderation'))->name('moderation');
```

- [ ] **Step 9: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Pulse/ModerationQueueTest.php`
Expected: PASS (6 tests)

- [ ] **Step 10: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 9 if it reformats anything.

- [ ] **Step 11: Commit**

```bash
git add app/Models/User.php app/Policies/PulsePostPolicy.php app/Livewire/Pulse/ModerationQueue.php \
  resources/views/livewire/pulse/moderation-queue.blade.php resources/views/pulse/moderation.blade.php \
  routes/web.php tests/Feature/Pulse/ModerationQueueTest.php
git commit -m "feat: moderation queue for AI-held posts

New /moderation route + ModerationQueue Livewire component: lists
every post AI held (status: 'flagged'), shows its original signal
(rejected vs flagged) and rationale, and lets a moderator/admin
approve (publish) or reject (remove, reason required).

PulsePostPolicy::moderate() makes PulseProfile.role real for the
first time -- the column already had moderator/admin values, checked
by zero authorization code before this. User::pulseProfile() is a
small missing relation this needed.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Full regression + manual verification

**Files:** none new — verification only.

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: 0 failures — confirms Tasks 1-2 didn't break `PulseTest`, the Jetstream scaffolding tests, or anything else.

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 1 if it reformats anything.

- [ ] **Step 3: Manual end-to-end verification**

```bash
php artisan tinker --execute '
$author = \App\Models\User::factory()->withPersonalTeam()->create(["name" => "Manual Test Author"]);
$moderator = \App\Models\User::factory()->withPersonalTeam()->create(["name" => "Manual Test Moderator"]);
\App\Models\PulseProfile::create(["user_id" => $moderator->id, "role" => "moderator"]);

$post = \App\Models\PulsePost::create([
    "user_id" => $author->id, "team_id" => $author->currentTeam->id,
    "type" => "discussion", "body" => "Buy cheap followers now!!!", "status" => "pending",
]);

$service = new \App\Services\AiModerationService(mock: true);
$service->upsertEnrichment($post, [
    "moderation_status" => "rejected", "moderation_rationale" => "Looks like spam.",
]);

echo "post status after AI rejection: {$post->fresh()->status}\n";
echo "author_id={$author->id} moderator_id={$moderator->id} post_id={$post->id}\n";
'
```

Expected: `post status after AI rejection: flagged` (held, not removed).

Then, as the moderator, approve it through the real Livewire component:

```bash
php artisan tinker --execute '
auth()->loginUsingId(<moderator_id>);
$post = \App\Models\PulsePost::find(<post_id>);
$component = new \App\Livewire\Pulse\ModerationQueue();
$component->mount();
$component->approve($post->id);
echo "post status after moderator approves: {$post->fresh()->status}\n";
$log = \App\Models\PulseModerationLog::where("target_id", $post->id)->latest()->first();
echo "log action={$log->action} is_ai_decision={$log->is_ai_decision} moderator_id={$log->moderator_id}\n";
'
```

Expected: `post status after moderator approves: published`, and a `PulseModerationLog` row with `action=approve`, `is_ai_decision=` (empty/false), `moderator_id=<moderator_id>`. Confirms the real end-to-end lifecycle, not just in-memory test doubles.

- [ ] **Step 4: Clean up manual verification fixtures**

```bash
php artisan tinker --execute '
$post = \App\Models\PulsePost::where("body", "Buy cheap followers now!!!")->first();
if ($post) { \App\Models\PulseModerationLog::where("target_id", $post->id)->delete(); $post->enrichment?->delete(); $post->delete(); }
foreach (["Manual Test Author", "Manual Test Moderator"] as $name) {
    $u = \App\Models\User::where("name", $name)->first();
    if ($u) { \App\Models\PulseProfile::where("user_id", $u->id)->delete(); $u->currentTeam?->delete(); $u->delete(); }
}
echo "cleaned up manual verification fixtures\n";
'
```

- [ ] **Step 5: Report completion**

No commit for this task — it's verification only. If Step 1 finds any failures, stop and fix them (return to the relevant earlier task) before considering this plan complete.

## Self-Review Notes

- **Spec coverage:** Task 1 covers spec §1 (`upsertEnrichment()` holds instead of removing, logs AI decisions). Task 2 covers spec §2 (`PulsePostPolicy`) and §3 (`ModerationQueue` + route). Task 3 covers the spec's implicit "this all actually works together" requirement.
- **Placeholder scan:** none — every step has literal file content, including full model/policy/Livewire/view/migration-free contents.
- **Type consistency:** `AiModerationService::upsertEnrichment(PulsePost $post, array $data): PulsePostEnrichment`, `PulseModerationLog::create()`'s fillable keys, `PulsePostPolicy::moderate(User $user): bool`, and `ModerationQueue`'s `approve`/`promptReject`/`cancelReject`/`confirmReject` signatures and `$rejectingPostId`/`$rejectReason` properties are used identically everywhere they're referenced across both tasks.
- **Policy naming correction, addressed explicitly:** the spec's prose used "PostPolicy" as shorthand; the Global Constraints section and Task 2 Step 4 both correct this to `PulsePostPolicy` — the name Laravel's auto-discovery actually requires for the `PulsePost` model, checked against this repo's own working `TeamPolicy` example rather than assumed.
- **Testability fix, justified inline:** Task 1 Step 5 explains why `upsertEnrichment()` needs to go from `private` to `public` (no `Http::fake()` available over raw `curl_init()`), rather than silently changing visibility with no rationale.
- **Both dead-ends closed, not just the flagged one:** Task 1's branching handles `rejected` and `flagged` identically (both hold at `status: 'flagged'`), and both get their own test — matching the spec's explicit inclusion of the second, previously-undiscussed dead-end.
- **No new schema:** confirmed no migration file appears in either task — both `pulse_posts.status`'s `'flagged'` value and the `pulse_moderation_logs` table already exist; this plan only adds code that finally uses them.
