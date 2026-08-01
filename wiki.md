---
title: Dot.Pulse — Platform Wiki
version: 1.0.0
status: active
owners: [Pulse Platform Lead]
platform-id: dot-pulse
last-review: 2026-08-01
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
| 1.0.0 | 2026-08-01 | Pulse Platform Lead | Initial wiki: derived from the real Laravel codebase (models, migrations, routes, services) with cross-reference to Dot.Brain's platforms/dot-pulse.md; explicitly separates shipped functionality from planned ecosystem integration |
| 1.0.1 | 2026-08-01 | Platform Loop Pass | Engineering-quality pass: wired the real Dot.Pulse logo into favicons, the nav/brand mark components, and the auth card (replacing generic Jetstream placeholder marks and an unreferenced dead `components/welcome.blade.php` leftover, plus a stray `public/dot_projects.png` from another platform's template); added a loading state and missing paginator to `HomeFeed`; added `tests/Feature/Pulse/HomeFeedTest.php`; removed the stale duplicate Laravel 12/PHP 8.4 README section flagged in §7. AI moderation, knowledge-graph extraction, Reverb wiring, and the REST API were not touched, per this pass's bounded scope. Route/policy scan found no unauthenticated gaps — `/moderation` is properly role-gated in `PulseController::moderation`, and all API v1 routes sit behind `auth:sanctum`. |

## Open Questions

- Does the topic-signal pack get computed from the existing `pulse_knowledge_nodes`/`pulse_knowledge_edges` tables, or does it need its own aggregation pipeline over `pulse_post_enrichments`?
- Who owns building and certifying the privacy-gate contract (Pulse engineering vs. a shared Dot.Brain privacy-tooling library)?
- Should `MarkSolution`, `EnrichPost`, and the trending-topics scheduled command be the direct triggers for the three ecosystem events in §5, or should a dedicated outbox/event-bridge layer sit between local Laravel events and Knowledge Pack publication?
