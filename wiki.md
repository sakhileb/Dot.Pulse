---
title: Dot.Pulse — Platform Wiki
version: 1.3.0
status: active
owners: [Pulse Platform Lead]
platform-id: dot-pulse
last-review: 2026-08-04
---

# Dot.Pulse

Purpose: this is Dot.Pulse's own knowledge home — owned and maintained by the Dot.Pulse team. It describes what this platform actually is, how it is built, and how it connects to the wider Dot Ecosystem. Dot.Brain never edits this file; it only reads what we choose to publish.

> **Related:** [Dot.Brain's ingested view of this platform](https://github.com/sakhilebhayi/Dot.Brain/blob/main/platforms/dot-pulse.md)

---

## 1. What Dot.Pulse Is

Dot.Pulse is the community intelligence layer of the Dot ecosystem — a Laravel application where members join communities, post across 17 content types (Discussion, Question, Idea, Bug Report, Release, Success Story, Showcase, Tutorial, Agent, Integration, Event, Article, Poll, Video, Job, Marketplace, Announcement), comment in threads, react, follow each other, and message directly. It is closer to Stack Overflow + Reddit + a small marketplace than a generic social feed: the reputation system rewards accepted solutions specifically, not raw engagement.

**Status: built and running.** Unlike a greenfield platform, this is a real, working Laravel 13 (PHP 8.3) app with 41 database tables, a REST API, real-time channels, an AI moderation pipeline, and a passing test suite (54 tests / 98 assertions per the current README). This wiki describes what is actually implemented, distinguishing it explicitly from what is only planned.

The README at the repo root currently contains two overlapping descriptions of the project (an older "Laravel 12 / PHP 8.4 / Meilisearch / Horizon" section appended after a newer "Laravel 13 / PHP 8.3 / Reverb" section). `composer.json` confirms the newer section is accurate — `laravel/framework: ^13.8`, `php: ^8.3`, `laravel/reverb: ^1.0`, `laravel/sanctum: ^4.0`. This wiki treats the newer section as authoritative and flags the stale duplicate for cleanup (§7).

## 2. Architecture

Verified directly from `app/`, `routes/`, `database/migrations/`, and `composer.json`:

| Layer | Technology |
|---|---|
| Framework | Laravel 13, PHP 8.3 |
| Frontend | Livewire 3, Alpine.js 3, Tailwind CSS, Vite |
| Database | PostgreSQL (production), SQLite (local dev) |
| Real-time | Laravel Reverb (WebSockets) + Laravel Echo |
| Auth | Laravel Jetstream (teams) + Fortify + Sanctum |
| Queue | Database queue |
| AI | Anthropic Claude via direct HTTP call (`app/Services/AiModerationService.php`), with a mock fallback when no API key is configured |

Code organization follows an Actions/Jobs/Events/Livewire pattern:

- **Actions** (`app/Actions/Pulse/`) — single-responsibility write operations: `AddComment`, `CreateCommunity`, `CreateEvent`, `FollowUser`, `MarkSolution`, `PublishMarketplaceItem`, `ReportContent`, `SendMessage`, `SubmitReview`, `UploadMedia`, `VotePoll`.
- **Jobs** — `EnrichPost` runs AI moderation/enrichment asynchronously (3 retries, backoff).
- **Events** (broadcast via Reverb) — `CommentPosted` (`post.{id}` channel), `MessageSent` (`conversation.{id}` private channel), `PostPublished` (feed + `community.{id}` channels). These are Laravel broadcast events for the browser UI, not ecosystem Knowledge Pack events — see §5 for that distinction.
- **Livewire components** (`app/Livewire/Pulse/`) — 16 components covering the feed, community management, post creation, moderation queue, messaging, search, marketplace, events, and profile.
- **Services** — `AiModerationService` (calls Claude, falls back to a mock scorer), `BadgeAwarder` (badge eligibility checks), `KnowledgeGraphService` (extracts problem/product/expert/concept nodes and edges from approved, enriched posts — see `app/Services/KnowledgeGraphService.php`).
- **Policies** — `CommentPolicy`, `CommunityPolicy`, `PostPolicy`.

### AI moderation pipeline (verified)

`app/Services/AiModerationService.php` posts each new post to `https://api.anthropic.com/v1/messages` (model `claude-sonnet-4-6`) requesting spam score, safety score, sentiment, summary, tags, topics, and keywords; on any non-200 response or missing API key it falls back to a deterministic mock enrichment so the pipeline never blocks post creation. Output is stored in `pulse_post_enrichments` and consumed by `KnowledgeGraphService`, which — only for posts with `moderation_status = approved` — upserts `PulseKnowledgeNode`/`PulseKnowledgeEdge` rows: concept nodes per topic, problem nodes for `question` posts, product nodes for `success_story`/`showcase`/`release` posts, and expert nodes linking authors with `solutions_accepted > 0` to their topics.

### REST API (verified from `routes/api.php`)

Sanctum-token-protected, prefixed `/api/v1`: feed, posts (CRUD + react + report), comments, communities (CRUD + join/leave), follow, notifications, and search.

### Ecosystem SSO (verified from `routes/web.php`)

`GET /auth/ecosystem?token={sanctum_token}` (`EcosystemAuthController`) accepts a Sanctum token from the wider InfoDot/Dot hub and logs the user in — this is the platform's one confirmed piece of cross-platform integration code today.

## 3. Domain Entities Owned

From `database/migrations/2026_06_29_000001_create_pulse_core_tables.php`, `..._000002_create_pulse_extended_tables.php`, and `app/Models/`:

| Domain | Tables / Models |
|---|---|
| Identity | `users`, `pulse_profiles`, `pulse_followers`, `pulse_user_badges`, `pulse_badges` |
| Content | `pulse_posts`, `pulse_post_enrichments`, `pulse_comments`, `pulse_reactions`, `pulse_hashtags`, `pulse_media` |
| Communities | `communities`, `community_memberships`, `community_rules` |
| Polls | `pulse_polls`, `pulse_poll_votes` |
| Events | `pulse_events`, `pulse_event_rsvps` |
| Marketplace | `pulse_marketplace_items`, `pulse_marketplace_installs` |
| Reviews | `pulse_reviews` |
| Messaging | `pulse_conversations`, `pulse_conversation_user`, `pulse_messages` |
| Moderation | `pulse_reports`, `pulse_moderation_logs` |
| Knowledge graph | `pulse_knowledge_nodes`, `pulse_knowledge_edges` |
| Teams (Jetstream) | `teams`, `team_user`, `team_invitations` |

Reputation mechanics are outcome-anchored, not raw-engagement: posts earn 5 points, comments 2 points, and an accepted solution 10 points (`app/Actions/Pulse/MarkSolution.php`, `app/Services/BadgeAwarder.php`). Badges include First Post, Problem Solver, AI Builder, Mentor, Verified Expert, and 7-Day Streak.

This maps onto Dot.Brain's `platforms/dot-pulse.md` framing of Community / Thread / Moderation-record ownership, though our concrete unit is the **Post** (17 typed variants), not a generic "Thread" — Dot.Brain's ingested naming is a simplification of the real schema.

## 4. What Dot.Pulse Does Not Yet Do

To keep this wiki honest about the gap between the ecosystem-level vision in `platforms/dot-pulse.md` and shipped code:

- No code in this repo publishes, signs, or queues Dot Knowledge Packs (DKP). There is no `dkp:` manifest, no `platform.dkp.json`, and no privacy-gate/aggregation logic matching the five-gate contract described on the Brain side. The knowledge graph (§2) is a local feature (problem/solution/expert graph for in-app discovery) — it is not wired to Dot.Brain today.
- No topic-signal aggregation, cross-org cohort-floor enforcement, or re-identification checks exist in code. Dot.Brain's `dot-pulse.md` describes these as an already-closed "registry gap" from the ecosystem side; on the platform side they remain to be built.
- No explicit code references Dot.Dopemine's prohibited-metric list. In practice the shipped mechanics already lean outcome-anchored (points for accepted solutions, not raw like-counts as a target, no visible streak-loss framing in the UI code reviewed), which is consistent with the posture Dot.Brain's doc describes — but that alignment has not been formally reviewed or certified against dot-dopemine's list.

## 5. Events — Local vs. Ecosystem

Two different meanings of "event" apply here and should not be conflated:

**Local broadcast events (verified, shipped):** `PostPublished`, `CommentPosted`, `MessageSent` — Laravel event classes broadcast over Reverb to drive the live UI (feed updates, comment threads, messaging). These never leave the platform and carry no ecosystem semantics.

**Ecosystem Knowledge Pack events (planned, per Dot.Brain's `platforms/dot-pulse.md`):** `social.thread.resolved/expired`, `social.topic.trending`, `social.moderation.case_closed`. These are Dot.Brain's proposed integration surface — they describe events Pulse *could* emit toward the Brain once packs are wired up. None of the three currently exist as code in this repository. The closest existing signal is `MarkSolution` (an Action, not yet an emitted ecosystem event) and `pulse_post_enrichments`/`KnowledgeGraphService` output (local only, not published).

## 6. Connecting to Dot.Brain

Dot.Pulse is a registered platform (`dot-pulse`) in the ecosystem's knowledge architecture, but integration is not yet implemented in this codebase. The plan, per Dot.Brain's ingested view, is to publish four Knowledge Pack types once the privacy-review gate is built:

| Payload type | Planned cadence | Would carry |
|---|---|---|
| `observation` | weekly | topic-signal and resolution-rate aggregates |
| `insight` | per finding | recurring-problem findings from topic clusters |
| `outcome` | per verified recommendation | verification results (e.g. expertise-routing impact) |
| `incident` | per incident | privacy-gate failures, moderation-pattern incidents |

The distinctive contribution Dot.Brain envisions for Pulse is the **topic-signal pack**: when the same operational problem surfaces independently across multiple organizations' communities, that convergence is a genuine early-warning signal the Brain cannot get from any other platform — carrying topic label, cohort size, and trend direction, with zero quotes, usernames, or thread links. Building this requires the aggregation and privacy-gate layer described in §4, none of which exists yet.

Full manifest shape, entity/event mapping, the privacy-gate design, and a worked publish round-trip are maintained on the Brain side at [`platforms/dot-pulse.md`](https://github.com/sakhilebhayi/Dot.Brain/blob/main/platforms/dot-pulse.md) — that document is Dot.Brain's ingested (and partly aspirational) view; this wiki is authoritative for what Dot.Pulse actually *is* today.

## 7. Roadmap / Open Questions

- [ ] Build the DKP publishing layer: `platform.dkp.json` manifest, pack signing, and a publish pipeline from `pulse_post_enrichments` / `pulse_knowledge_nodes` toward Dot.Brain.
- [ ] Implement the discussion-pack privacy gate (content exclusion, cohort floor ≥ 50 participants / ≥ 5 orgs for cross-org signals, re-identification check) before any topic-signal pack can ship — currently zero enforcement code exists for this.
- [ ] Emit the three ecosystem events Dot.Brain expects (`social.thread.resolved/expired`, `social.topic.trending`, `social.moderation.case_closed`) from the existing `MarkSolution` action and moderation/trending jobs.
- [ ] Formally review shipped reputation/notification mechanics (points, badges, streaks, notification bell) against Dot.Dopemine's prohibited-metric list rather than relying on incidental alignment.
- [ ] Clean up `README.md`: it currently contains a stale duplicate "Laravel 12 / PHP 8.4" section below the accurate "Laravel 13 / PHP 8.3" section; should be removed to avoid confusing new contributors.
- [ ] Decide whether the local knowledge graph (`pulse_knowledge_nodes`/`edges`) feeds ecosystem observation packs directly, or whether topic-signal aggregation is a separate computation over the same underlying enrichment data.

## Change Log

| Version | Date | Author | Change |
|---|---|---|---|
| 1.3.0 | 2026-08-04 | Platform-loop pass | Follow-up ecosystem-wide null-currentTeam audit (companion to Dot.Mines commit `0cc4362`'s `Auth::user()->currentTeam` fix in `Dashboard.php`). This platform has no team-context middleware at all (`bootstrap/app.php`'s `withMiddleware` is empty) — Jetstream's own `HasTeams::currentTeam()` is the only null-avoidance in play, and it self-heals by switching to the user's `personalTeam()` only when one exists; a user with no personal team (the default `UserFactory` state used throughout this test suite — `current_team_id` is null and no owned team is attached, with `withPersonalTeam()` as the explicit opt-in) reaches every authenticated route with a genuinely null `currentTeam`. Grepped `app/Livewire/*.php`, `app/Http/Controllers/**/*.php`, `app/Jobs/*.php`, `app/Console/Commands/*.php` for `currentTeam`/`current_team_id`: all four occurrences (`CreateCommunity.php:44`, `CreatePost.php:69`, `Api/V1/CommunityController.php:50`, `Api/V1/PostController.php:52`) already use the `?->id` nullsafe operator when setting `team_id` at create time — consistent with this platform's prior tenant-scope pass establishing `team_id` as create-time-only attribution, not a read filter, so no fix was needed there. The actual bug was in the view layer, not app/: `resources/views/navigation-menu.blade.php` (Jetstream's own published Livewire nav partial, rendered via `@livewire('navigation-menu')` in `layouts/app.blade.php` on literally every authenticated page) dereferenced `Auth::user()->currentTeam->name` and `->currentTeam->id` with zero null guard, gated only on `Jetstream::hasTeamFeatures()` (true here) — not on `currentTeam` actually being set. This is not a theoretical crash: Laravel's `HandleExceptions` bootstrapper runs `error_reporting(-1)` and converts PHP warnings (including "Attempt to read property on null") into a thrown `ErrorException`, so a signed-in user with no personal team got a 500 on every page load, including `layouts/app.blade.php`'s own `Auth::user()->currentTeam->name ?? 'Personal'` sidebar/topbar team label — the `??` never got a chance to fire because the property-access warning threw first. Fixed both files with the nullsafe operator (`?->`) plus an explicit `@if (Auth::user()->currentTeam)` guard around the "Team Settings" link (kept "Create New Team" reachable regardless, so a team-less user isn't stranded); the team-name trigger now falls back to `__('No Team')` instead of crashing. No duplicate logic to centralize — this was two isolated view-layer occurrences, not a repeated controller/Livewire pattern. Added `tests/Feature/Pulse/NavigationMenuTest.php` (2 tests) asserting `/dashboard` returns 200 for both a team-less and a team-having user, mirroring Dot.Mines' `DashboardTest` regression-test pattern adapted to Dot.Pulse's actual routes (this platform has no `teams.create`-redirect middleware to assert against, since none exists — the fix here is crash-prevention, not a new redirect flow). Spot-checked the `HasUserScope`/`HasTeamScope`/`HasOrganizationScope` rollout: only `HasUserScope` exists in this codebase (`app/Models/Concerns/HasUserScope.php`), applied to `PulseReaction`, `PulseEventRsvp`, `PulseMarketplaceInstall` exactly as the 1.2.0 entry describes; confirmed `Community` and `PulsePost` still carry no team read-filtering anywhere (`team.php`, `PulsePost.php` — no `HasTeamScope`, no `where('team_id', ...)` read filter in any controller/Livewire/API path), so the public-content decision from the prior pass is intact and was **not** reopened. Full suite re-run against real PostgreSQL 16 in an isolated `dot_pulse_test_audit` database (created and dropped for this run only, `.env`/`.env.testing` untouched — `phpunit.xml`'s forced-sqlite test env was temporarily overridden for the run and restored byte-for-byte afterward): 106 tests, 99 passed, 0 failed, 7 skipped (up from 104/97/0/7 — the two new tests). No schema/migration/RLS changes. Queue/cache/search-index config not touched, no new concerns found there this pass. |
| 1.2.0 | 2026-08-04 | Platform-loop pass | Architecture pass matching the Dot.Mines/Dot.Finance/Dot.Notify pattern (see Dot.Finance commit `2f75bdb`, Dot.Notify commit `e671436`): added `App\Models\Concerns\HasUserScope`, an Eloquent global scope trait applied to `PulseReaction`, `PulseEventRsvp`, and `PulseMarketplaceInstall`, scoping every query on those three models to `Auth::id()` automatically. Choosing `HasUserScope` over Dot.Notify's `HasTeamScope` was a deliberate, code-verified call, not the naive "Jetstream teams enabled → team-scope everything" default: `team_id` appears on exactly two models here (`Community`, `PulsePost`), and grepping every controller/Livewire/Action confirmed `team_id` is used *only* for create-time attribution (`CreateCommunity`, `CreatePost`, the API equivalents) — there is no `where('team_id', ...)` read-filter anywhere in this codebase, because communities and posts are Dot.Pulse's public feed/catalog surface (Stack Overflow + Reddit style), not private tenant data; scoping them by team would have broken the product's core function, not secured it. The three models that *do* carry genuinely private, per-user state — "did I RSVP to this event" (`PulseEventRsvp`), "did I react to this post/comment" (`PulseReaction`), "which marketplace items have I installed" (`PulseMarketplaceInstall`) — all key off a plain `user_id` column instead, the same single-user shape as Dot.Finance. Deliberately **not** applied to `PulsePost`, `PulseComment`, `PulseProfile`, `PulseReview`, `PulseUserBadge`, `Community`, `CommunityMembership`, or `PulsePollVote` (`VotePoll::getResults()` aggregates vote counts across *all* users for public poll results — a global user scope there would have silently broken every poll) or `PulseMessage`/`PulseConversation` (conversation access is participant-based via a pivot table, not a single `user_id` owner column — messaging in this Livewire component already correctly scopes via `whereHas('participants', ...)`, which a blanket `user_id` scope on `PulseMessage` would have broken by hiding other participants' messages). Removed now-redundant explicit `where('user_id', ...)` read filters from `EventsList::rsvp()` and `PostDetail::react()`; the `create()` calls' explicit `'user_id' => Auth::id()` mass-assignment in both were left untouched, matching every read-scoping pass in this rollout. **No 403→404 test-assertion changes were needed**: none of the three scoped models are gated by a Policy or exposed via implicit route-model binding (`app/Policies/` only covers `Comment`/`Community`/`Post`/`Team`), so there was no pre-existing 403 behavior to change. Added `tests/Feature/Pulse/PulseUserScopeTest.php::test_scope_alone_blocks_cross_user_access_even_without_an_explicit_where`, proving the scope alone (no Policy, no explicit `where`) blocks cross-user reads of `PulseReaction`. Full suite re-run after all changes: 104 tests, 97 passed, 7 skipped, 0 failed (up from 103/96/0/7 — the one new test), against real PostgreSQL 16 in an isolated `dot_pulse_pilot` database. Added `phpstan.neon.dist` (level 5, Larastan extension) as dev tooling — unlike every other platform in this rollout, `vendor/bin/phpstan analyse --memory-limit=1G` actually executed in this session's sandbox and returned 43 pre-existing type errors (truncated), none in any file touched by this pass (`HasUserScope.php`, `PulseReaction.php`, `PulseEventRsvp.php`, `PulseMarketplaceInstall.php`, `EventsList.php`, `PostDetail.php`) — left as pre-existing debt outside this pass's scope, not fixed here. `composer audit` found 6 pre-existing advisories (1 high, 5 medium) against `guzzlehttp/guzzle` predating this pass; fixed via `composer update guzzlehttp/guzzle guzzlehttp/psr7 guzzlehttp/promises --with-all-dependencies` (7.12.3 → 7.15.2) — `composer audit` clean afterward, full test suite re-confirmed green (104/97/0/7) post-update. |
| 1.1.1 | 2026-08-03 | Pulse Platform Lead | First real-execution verification pass: ran `composer install`, `php artisan migrate`, and `php artisan test` for the first time against an actual PHP 8.5/PostgreSQL 16 runtime (this repo's `composer.json` allows `^8.3` and installed cleanly under the default Homebrew PHP 8.5 — no 8.3 fallback was needed). Migrations ran clean on the first try against an isolated `dot_pulse_verify` database. `php artisan test` surfaced a real, stale-test bug: `ModerationTest::test_enrich_post_job_falls_back_to_published_on_failure` still asserted the pre-1.0.2 fail-open behavior (auto-publish an unmoderated post when AI enrichment permanently fails) that the 1.0.2 security deep-dive pass (`0f6184c`) deliberately replaced with a fail-safe `'flagged'` status — the job code was correctly hardened but this one test was never updated to match, so it failed asserting `'published'` against the now-correct `'flagged'` outcome. Fixed by updating the test to assert the intended fail-safe behavior and renaming it to `test_enrich_post_job_flags_for_moderation_on_failure`; the job itself (`app/Jobs/EnrichPost.php`) was not touched, since its fail-closed behavior is correct and intentional. Final suite: 103 total, 96 passed, 0 failed, 7 skipped (pre-existing Jetstream feature-flag gates on email verification/API tokens/2FA, not new). Also applied the ADR-0013 idempotent-guard pattern (`Schema::hasTable`/`hasColumn` checks) to this platform's six shared Jetstream-core migrations (`create_users_table`, `add_two_factor_columns_to_users_table`, `create_personal_access_tokens_table`, `create_teams_table`, `create_team_user_table`, `create_team_invitations_table`), which had not yet been guarded; re-ran migrate and the full test suite against a fresh database afterward and confirmed identical 103/96/0/7 counts, so the guard is behavior-neutral. See Dot.Brain adr/ADR-0013 for the shared-database rationale. |
| 1.1.0 | 2026-08-03 | Sakhile Bhayi | Redesigned `resources/views/welcome.blade.php`'s marketing surface: the nav's placeholder icon-box mark is now the real `public/images/logo.png` lockup, and a real, licensed Unsplash photo of a diverse team collaborating around a table (photo by Vitaly Gariev, @silverkblack, unsplash.com/photos/diverse-team-collaborates-around-a-table-in-office-fm4B1xWEIsU) now backs the hero section, hotlinked via Unsplash's CDN with a dark gradient overlay layered under the existing radial-gradient accent for WCAG-adequate text contrast, photographer credited inline as an HTML comment; added a matching logo mark to the footer, which previously had no brand mark at all. Also fixed a pre-existing bug in this file unrelated to the marketing copy itself: the actual custom marketing page ended at its own `</html>` (line 125), but roughly 220 lines of a dead, never-rendered duplicate default-Jetstream-scaffold document (a second `<head>`/`<body>`) were concatenated after it — removed as dead weight while in the file for the required change, not a scope expansion. The CDN image URL was verified with `curl -sI` before use (HTTP/2 200). |
| 1.0.0 | 2026-08-01 | Pulse Platform Lead | Initial wiki: derived from the real Laravel codebase (models, migrations, routes, services) with cross-reference to Dot.Brain's platforms/dot-pulse.md; explicitly separates shipped functionality from planned ecosystem integration |
| 1.0.1 | 2026-08-01 | Platform Loop Pass | Engineering-quality pass: wired the real Dot.Pulse logo into favicons, the nav/brand mark components, and the auth card (replacing generic Jetstream placeholder marks and an unreferenced dead `components/welcome.blade.php` leftover, plus a stray `public/dot_projects.png` from another platform's template); added a loading state and missing paginator to `HomeFeed`; added `tests/Feature/Pulse/HomeFeedTest.php`; removed the stale duplicate Laravel 12/PHP 8.4 README section flagged in §7. AI moderation, knowledge-graph extraction, Reverb wiring, and the REST API were not touched, per this pass's bounded scope. Route/policy scan found no unauthenticated gaps — `/moderation` is properly role-gated in `PulseController::moderation`, and all API v1 routes sit behind `auth:sanctum`. |
| 1.0.2 | 2026-08-01 | Security Deep-Dive Pass | Follow-up pass specifically on the moderation/AI internals this platform's S=1 flag (Dot.Brain `15-MEGA-v2.md`) called out as unaudited. See "Security Review Findings" below for the fail-open moderation bug and private-community IDOR fixed, plus the knowledge-graph tenant-isolation gap flagged but left unfixed. |

## Security Review Findings (2026-08-01 deep-dive pass)

Scoped follow-up on the AI moderation pipeline and knowledge-graph service, previously out of scope. Hand-review only (no PHP/Composer/DB available), stayed on `feature/ecosystem-sso`.

**Fixed:**

- **Moderation fail-open on permanent job failure** (`app/Jobs/EnrichPost.php::failed()`). If the `EnrichPost` job exhausted its 3 retries (Claude API down, etc.), the handler auto-published the post with `status = 'published'` — zero moderation, zero enrichment record, no human ever in the loop. Changed to set `status = 'flagged'` instead, so the post stays out of `published()`/`scopeFeed()` results (never reaches other users) rather than defaulting to live. Note: it still won't surface in `ModerationQueue` today because that Livewire component queries `PulsePostEnrichment`, not `PulsePost` directly, and a permanently-failed job never creates an enrichment row — so these posts land in a safe-but-invisible limbo. A follow-up should add a "failed enrichment" tab to the queue that lists `PulsePost::where('status','flagged')->whereDoesntHave('enrichment')`.
- **Private-community posts/comments leaked to any authenticated user.** `PostPolicy::view()` only checked post status, never community visibility, and several entry points didn't call it at all: `PulseController::post()` (web post-detail page), `PostDetail::mount()` (Livewire), and API `PostController::show()` all loaded posts with no authorization call whatsoever; `PostController::index()`/`HomeFeed::posts()` filtered by `published()` only, with no community-visibility filter; `CommentController::index()` and the `AddComment` action didn't check post visibility at all. Net effect: any signed-in user (any team, any org) could read and comment on posts/comments belonging to `private`/`enterprise` communities they weren't members of, via both the web UI and the API — the exact cross-tenant class of bug flagged for this pass. Fixed by extending `PostPolicy::view()` to gate non-public-community posts on `Community::hasMember()`, adding a `PulsePost::scopeVisibleTo()` query scope for list views, and wiring `authorize('view', $post)` / the new scope into all five entry points above. `CommunityController::show()` already did this correctly (`$this->authorize('view', $community)`) — it was the pattern to match, not a new one.

**Flagged, not fixed (too deep for a hand-review pass per this session's scope):**

- **`pulse_knowledge_nodes`/`pulse_knowledge_edges` have no tenant scoping at all.** `KnowledgeGraphService::upsertNode()` upserts nodes globally by `(entity_type, label)` with no `team_id`/`community_id` column on the table, and `sources` (which holds `post_id`/`user_id`) is just appended data, not a scope key. Concretely: if Team A's private community and Team B's private community both produce a post tagged with the topic "Kubernetes," they get merged into the *same* concept node, and an "expert" node for a user is keyed only by name with no team qualifier. Today this isn't exploitable through any UI/API — grepped the whole app and found no controller, Livewire component, or route that reads `PulseKnowledgeNode`/`PulseKnowledgeEdge` at all, so nothing currently exposes the merged graph to end users. But the data model itself is not tenant-safe, and the moment anyone builds a "knowledge graph" view (which `wiki.md` §7 already lists as a roadmap item, and which Dot.Brain's topic-signal pack depends on), it will need a schema change (add `team_id`/`community_id` to nodes, or a join table) before it can be exposed — not a query-level fix. Left the core extraction algorithm untouched per this pass's explicit instructions.
- **Fail-open at the AI-call level, not just the job-retry level, and it's silent.** Even when `AiModerationService::callClaude()` succeeds at the HTTP layer but Claude's response isn't valid/parseable JSON (regex match on `\{.*\}` fails, or the model omits a field), `upsertEnrichment()` defaults `moderation_status` to `'approved'` (see the `??` fallback chain and the `'moderation_status' => 'approved'` default in `mockEnrichment()`/the migration's column default). There's no "if parsing failed, treat as unmoderated/pending" branch — a malformed model response is indistinguishable from an explicit approval. This is a real fail-open path but it's inside the core moderation decision logic (`upsertEnrichment`), which this pass was told not to modify; flagging for whoever owns that service to add a "parse failure → pending, not approved" branch.

## Open Questions

- Does the topic-signal pack get computed from the existing `pulse_knowledge_nodes`/`pulse_knowledge_edges` tables, or does it need its own aggregation pipeline over `pulse_post_enrichments`?
- Who owns building and certifying the privacy-gate contract (Pulse engineering vs. a shared Dot.Brain privacy-tooling library)?
- Should `MarkSolution`, `EnrichPost`, and the trending-topics scheduled command be the direct triggers for the three ecosystem events in §5, or should a dedicated outbox/event-bridge layer sit between local Laravel events and Knowledge Pack publication?
