# Dot.Pulse: AI Content Moderation Approval Gate

## Context

Dot.Pulse's autonomy classification audit (`Dot.Brain/platforms/dot-pulse.md`, 2026-08-08) honestly flagged a real gap: `AiModerationService::upsertEnrichment()` (`app/Services/AiModerationService.php`) auto-publishes a post (`moderation_status: approved` → `$post->update(['status' => 'published'])`) or auto-removes it (`moderation_status: rejected` → `$post->update(['status' => 'removed'])`) purely from an unreviewed Claude classification, with no human step before either transition takes effect. That part of the audit is accurate and confirmed against the real code.

The audit's proposed remedy is not available as described, though. It suggests routing the `rejected` transition "into the existing moderator queue" — but direct inspection found that queue does not exist. Every piece of Level-3 infrastructure the audit describes (`app/Livewire/Pulse/ModerationQueue.php`, `PulseController`, `PostPolicy`, the `/moderation` route) is absent from the repo: `app/Http/Controllers/` has two files (neither is a `PulseController`), `app/Policies/` has only Jetstream's default `TeamPolicy`, `app/Events/` is empty, and `app/Livewire/Pulse/` has three components (`CommunityList`, `CreatePost`, `HomeFeed`) — no `ModerationQueue`. What *is* real: two unused tables from `database/migrations/2026_06_29_000002_create_pulse_extended_tables.php` — `pulse_moderation_logs` (columns: `moderator_id`, `target` morph, `action`, `rationale`, `is_ai_decision`) and `pulse_reports` — with zero application code reading or writing either, and `PulseProfile.role`, a real enum column (`customer, business, enterprise, developer, partner, moderator, admin`) that already carries `moderator`/`admin` values but is checked by zero authorization code anywhere in the app.

A second dead-end sits right next to the flagged one: `pulse_posts.status` is a native enum `['draft', 'pending', 'published', 'flagged', 'removed']` — `'flagged'` already exists in the schema, earmarked for exactly this kind of held-for-review state, but `upsertEnrichment()` never sets it. When Claude classifies a post as `flagged`, the method does nothing at all; the post stays at `'pending'` permanently, with no code path to review it, ever.

## Goal

Close both dead-ends with one queue, reusing dormant schema rather than adding new columns or tables. `upsertEnrichment()`'s `approved` path is untouched — it keeps auto-publishing (routine content isn't slowed by a review bottleneck). Its `rejected` path no longer auto-removes; both `rejected` and (newly) `flagged` land the post at `status: 'flagged'` and wait. A moderator or admin (`PulseProfile.role`, made real for the first time) reviews held posts on a new `/moderation` queue: approve publishes it, reject (reason required) removes it. Every decision — the AI's original one and the human's — is logged to the already-existing `pulse_moderation_logs` table, giving this gate a genuine audit trail for free.

## Changes

### 1. `AiModerationService::upsertEnrichment()` holds instead of auto-removing

No migration needed — `status: 'flagged'` already exists in the `pulse_posts.status` enum. The method's post-status branch changes from two cases to three:

- `approved` → `status: 'published'` (unchanged).
- `rejected` → `status: 'flagged'` (was `'removed'`).
- `flagged` (new branch — previously silently did nothing) → `status: 'flagged'`.

`PulsePostEnrichment.moderation_status` already stores `approved`/`flagged`/`rejected` distinctly (unchanged by this spec), so the queue can still tell "AI recommended reject" apart from "AI was just unsure" even though both land the post at the same `pulse_posts.status`. `PulsePostEnrichment` gains an `isRejected(): bool` helper alongside its existing `isApproved()`/`isFlagged()`, for symmetry and queue-view use.

Every branch also writes one `PulseModerationLog` row: `moderator_id: null`, `target()` morph to the post, `is_ai_decision: true`, `action` set to `approve`/`reject`/`flag` matching the branch, `rationale` copied from `PulsePostEnrichment.moderation_rationale`.

### 2. `PostPolicy::moderate()` — makes `PulseProfile.role` real

New `app/Policies/PostPolicy.php`, registered for `PulsePost`:

```php
public function moderate(User $user): bool
{
    return in_array($user->pulseProfile?->role, ['moderator', 'admin'], true);
}
```

`User` gains a `pulseProfile(): HasOne` relation (`app/Models/User.php`) — it doesn't exist today; every current reference to a user's `PulseProfile` (e.g. the `/dashboard` route) looks it up with a fresh query rather than through the model, because there's nowhere to hang the relation.

### 3. `ModerationQueue` Livewire component + `/moderation` route

New `app/Livewire/Pulse/ModerationQueue.php`, full-page component. `mount()` calls `$this->authorize('moderate', PulsePost::class)` — a non-moderator gets a 403, matching every gate built this session. Lists `PulsePost::where('status', 'flagged')->with(['enrichment', 'author', 'community'])->latest()->get()`; each card shows the post body/title, author, community, AI's original signal and rationale (`enrichment->moderation_status`, `enrichment->moderation_rationale`), and `spam_score`/`safety_score`.

Four actions, the same confirm-then-act shape used in every prior gate this session:

- `approve(int $postId)` — re-fetches the post; no-ops if it's no longer `'flagged'` (already handled by someone else). Otherwise sets `status: 'published'`, logs a `PulseModerationLog` row (`moderator_id: auth()->id()`, `is_ai_decision: false`, `action: 'approve'`).
- `promptReject(int $postId)` — enters reject-confirmation mode for that post (`$rejectingPostId`, `$rejectReason` component state).
- `cancelReject()` — clears reject-confirmation mode.
- `confirmReject(int $postId)` — no-ops if the reason is empty, or if the post is no longer `'flagged'`. Otherwise sets `status: 'removed'`, logs a row (`action: 'reject'`, `rationale`: the moderator's typed reason).

New route in `routes/web.php`, inside the existing `auth:sanctum` + `jetstream.auth_session` + `verified` group (matching `/dashboard`'s convention — no new middleware group needed, since authorization lives in the policy, checked in `mount()`):

```php
Route::get('/moderation', fn () => view('pulse.moderation'))->name('moderation');
```

`resources/views/pulse/moderation.blade.php` wraps `<livewire:pulse.moderation-queue />` in the existing `<x-app-layout>`, matching `dashboard.blade.php`'s structure.

## Testing

- `AiModerationService`: `rejected` and `flagged` both land at `status: 'flagged'` and do not remove the post; `approved` still auto-publishes; each path writes the expected AI-attributed `PulseModerationLog` row.
- `ModerationQueue`: a moderator can approve a held post (status → `published`, human log row written); a moderator can reject with a reason (status → `removed`, reason recorded); rejecting with an empty reason is a no-op (status stays `flagged`); a non-moderator/admin is blocked with a 403 from both the route and the component's actions directly.
- Acting on a post that's no longer `'flagged'` (already resolved by another moderator) is a no-op, not an error.

## Explicitly out of scope

- Per-community moderator roles (`community_memberships.role` already has its own `member`/`moderator`/`admin` values) — this gate uses the platform-wide `PulseProfile.role` only, per the design discussion; community-scoped moderation is a separate, future concern.
- `pulse_reports` (user-submitted content reports) — real, unused schema, but a distinct intake path from AI moderation; wiring it into this queue (or a queue of its own) is separate work.
- Restoring a post that reached `status: 'removed'` back to `published` — out of scope; a rejected/removed post stays removed (no "undo" flow), consistent with this being the terminal action in the gate, not a reversible one.
- Rate limiting or batching the moderation queue UI (e.g. pagination) — not designed for at the volumes this repo currently handles; can be added later without changing this gate's shape.
- Any change to `AiModerationService`'s `approved` path, or to when `enrichPost()` is triggered (still synchronous, inside `CreatePost::publish()`) — both are unchanged by this spec, per the design discussion.
