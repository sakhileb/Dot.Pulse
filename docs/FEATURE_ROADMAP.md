# Dot.Pulse Feature Roadmap
Version: 1.0 | Living Document | Classification: Internal

---

## Roadmap Purpose

This document tracks what has been built, what is in progress, and what comes next. It is the single source of truth for development prioritisation.

Update this document at the start of every sprint or development cycle.

---

## Current Platform State (v1.0 — July 2026)

### ✅ Built & Shipped

**Infrastructure**
- Laravel 13 + Livewire 3 + PostgreSQL + SQLite (dev)
- Jetstream teams with invitations
- Fortify auth (login, register, 2FA, password reset)
- Sanctum API tokens
- Laravel Reverb (WebSocket, configured)
- Laravel Echo (JavaScript client, configured)
- S3/local file storage with signed URLs
- Database queue (jobs, failed jobs)
- Vite build pipeline

**Content**
- 17 post types (Discussion, Question, Idea, Bug, Release, Showcase, Tutorial, Agent, Integration, Event, Article, Poll, Video, Job, Marketplace, Announcement, Success Story)
- Threaded comments (nested replies)
- Reactions (polymorphic, toggle)
- Hashtags with post count tracking
- File attachments on posts (images, PDF, video)
- Poll creation + live voting with percentage bars

**Communities**
- Create/join/leave communities
- Public, private, enterprise visibility
- Member roles: member, moderator, admin
- Community rules
- Pinned posts

**Feed & Discovery**
- AI-ranked smart feed (relevance score)
- Feed pagination (20 per page, cursor-based)
- Type filter
- Global search (posts, communities, users)
- Trending topics (cached, hourly recalculation)

**Profiles**
- Public user profiles with stats (points, followers, following, solutions)
- Avatar and cover photo upload
- Bio, headline, location, website, skills
- Badge display
- Follow/unfollow with notification
- "Message" button → direct conversation

**Marketplace**
- Browse and install items (agent, template, dashboard, prompt library, automation, integration, extension, workflow)
- Publish marketplace items (pending review)
- Install count and rating display

**Events**
- Create events (webinar, AMA, launch, demo, training, town hall, meetup)
- RSVP (going, maybe, not going)
- Community-scoped or platform-wide

**Messaging**
- Direct conversations between users
- Two-panel UI (conversation list + chat view)
- User search to start conversations
- Real-time (via Reverb when configured)

**Moderation**
- Async AI moderation via Claude (queued job)
- Post enrichment: summary, tags, sentiment, topics, keywords
- Spam, safety, business relevance, community scores
- Auto approve/flag/reject
- Human moderation queue (approve/reject with audit log)
- Content reporting with reason
- Role-based moderation access

**Notifications**
- Database notifications: NewCommentOnPost, NewFollower, PostReacted, SolutionAccepted, MentionNotification
- Live notification bell with unread count (30s polling)
- Dropdown with recent 8 notifications
- Mark all read
- Full notifications page

**Reputation**
- Community points (post: +5, comment: +2, solution accepted: +10)
- Solutions accepted counter
- Badges: First Post, Problem Solver, AI Builder, Mentor
- Badge display on profiles

**API**
- REST API v1 with 19 Sanctum-authenticated endpoints
- Posts CRUD, reactions, reports
- Comments CRUD
- Communities CRUD, join/leave
- Follow toggle
- Notifications (list, mark read, unread count)
- Search

**Security**
- Rate limiting (posts: 10/hr, comments: 40/hr, reactions: 30/min, follows: 20/min)
- PostPolicy, CommentPolicy, CommunityPolicy
- AuthorizesRequests on base Controller
- CSRF, XSS, mass assignment protection
- Auth facade usage (IDE-safe, no `auth()` helper)

**AI**
- Knowledge graph extraction (concepts, problems, experts, products)
- KnowledgeGraphService: nodes and edges from approved posts
- Mock fallback when API unavailable

**Scheduled Tasks**
- `pulse:trending` — hourly hashtag recalculation
- `pulse:badges` — daily badge eligibility checks

**Testing**
- 54 tests, 98 assertions (91 passing, 7 intentional skips)
- PostTest, CommentTest, CommunityTest, FollowTest, ModerationTest, MessagingTest

**Documentation**
- README.md (comprehensive)
- ENTERPRISE_VENDOR_SECURITY_BLUEPRINT.md
- CODING_STANDARDS.md
- API_STANDARDS.md
- DATABASE_STANDARDS.md
- TESTING_STANDARDS.md
- AI_GOVERNANCE.md
- PRIVACY_FRAMEWORK.md
- MONITORING_OBSERVABILITY.md
- DEVSECOPS_PIPELINE.md
- INCIDENT_RESPONSE.md
- MULTI_TENANT_ARCHITECTURE.md
- FEATURE_ROADMAP.md (this file)

---

## Next Development Priorities

### P0 — Must Have Before Customer Launch

| Feature | Why | Effort |
|---|---|---|
| Meilisearch / Scout integration | Real full-text search — current `LIKE` doesn't scale | Medium |
| `GET /api/v1/me` endpoint | Most common first API call for integrations | Small |
| Privacy Dashboard UI (`/settings/privacy`) | GDPR/POPIA compliance requirement | Medium |
| Data export (`GET /api/v1/me/data-export`) | GDPR right of access | Medium |
| Account deletion with data removal | GDPR right to erasure | Medium |
| Email notification system (Mailgun/SES) | `MAIL_MAILER=log` is dev-only | Small |
| Rate limit response headers | API clients need `X-RateLimit-*` headers | Small |
| Health check endpoints (`/health`, `/health/database`) | Required for load balancer + monitoring | Small |
| `uuid` column on all content tables | External APIs should never expose integer IDs | Medium |
| OpenAPI spec generation (L5-Swagger) | Needed for enterprise integrations | Medium |

### P1 — High Value, Next Sprint

| Feature | Why | Effort |
|---|---|---|
| Meilisearch Scout driver | Replace LIKE queries with indexed search | Medium |
| `POST /api/v1/posts/{id}/poll/vote` API endpoint | Poll voting only works via Livewire | Small |
| `POST /api/v1/events/{id}/rsvp` API endpoint | Events RSVP only works via Livewire | Small |
| `GET /api/v1/communities/{slug}/posts` | Common API pattern missing | Small |
| `PATCH /api/v1/me/profile` | Profile update via API | Small |
| Marketplace item management UI | Publish, edit, manage versions | Medium |
| Event management UI | Edit, cancel events | Small |
| Community management UI | Edit description, manage rules, ban members | Medium |
| Notification email templates | Real email delivery for notifications | Medium |
| Idempotency keys on create endpoints | Required for reliable mobile clients | Medium |
| Request ID middleware | Correlation tracking for logs | Small |
| `X-Request-ID` response headers | API debuggability | Small |

### P2 — Important, Following Sprint

| Feature | Why | Effort |
|---|---|---|
| TenantScope global scope | Multi-tenant isolation (see MULTI_TENANT_ARCHITECTURE.md) | Medium |
| Subdomain tenant routing | Enterprise customer isolation | Large |
| PHPStan Level 9 CI gate | Static analysis in pipeline | Small |
| GitHub Actions CI workflow | Automated testing + security scanning | Medium |
| Sentry error tracking | Production error monitoring | Small |
| Laravel Telescope (staging) | Developer debugging | Small |
| Webhook outgoing events | Ecosystem integration surface | Large |
| Poll creation UI in CreatePost | Currently polls have no creation UI | Small |
| Review system UI (`SubmitReview` action exists) | No UI yet | Medium |
| Report review in moderation queue | Reports exist but not surfaced in queue | Small |
| Virus scanning on file uploads | Security requirement | Medium |
| MIME type magic-byte validation | Beyond extension check | Small |
| Consent management table + UI | GDPR compliance | Medium |
| Cookie consent banner | GDPR/POPIA requirement | Small |
| Community analytics for admins | Post counts, member activity | Medium |

### P3 — Future Roadmap

| Feature | Quarter | Description |
|---|---|---|
| AI user assistant | Q3 2026 | "Summarise today's discussions in my communities" |
| Personalised feed recommendations | Q3 2026 | ML-based, not just AI score |
| Schema-per-tenant | Q3 2026 | Strong enterprise data isolation |
| Voice notes in messaging | Q3 2026 | Audio message support |
| Screen sharing in events | Q3 2026 | WebRTC integration |
| Business/company pages | Q4 2026 | LinkedIn-style company profiles |
| Verified business badges | Q4 2026 | Dot ecosystem business verification |
| Knowledge graph search UI | Q4 2026 | Browse the knowledge graph visually |
| Internal feed for enterprise | Q4 2026 | Private enterprise community feed |
| Employee directory | Q4 2026 | Enterprise people search |
| Translation agent | Q1 2027 | Real-time multilingual content |
| Mobile apps (React Native) | Q1 2027 | iOS + Android |
| GraphQL API | Q1 2027 | For complex frontend queries |
| Fraud detection model | Q1 2027 | Fine-tuned on platform patterns |
| Customer success agent | Q2 2027 | Auto-route unanswered questions |
| Leaderboards | Q2 2027 | Community, weekly, monthly |
| SOC 2 Type II audit | Q2 2027 | For enterprise sales |

---

## Technical Debt Register

| Item | Impact | Effort | Priority |
|---|---|---|---|
| Replace `LIKE` search with Scout/Meilisearch | High — doesn't scale | Medium | P0 |
| Add `uuid` to all tables | High — security best practice | Medium | P0 |
| Add `TenantScope` global scopes | High — multi-tenant requirement | Medium | P1 |
| Counter drift reconciliation command | Medium — counters may diverge | Small | P1 |
| Replace counter columns with real counts | Medium — denormalization risk | Large | P3 |
| Migrate AI calls to guzzle/http client | Low — current curl works | Small | P2 |
| Extract feed ranking to a dedicated Query class | Low — currently in Livewire | Small | P2 |
| Add Form Requests to all API endpoints | Medium — validation currently in controller | Medium | P1 |
| Add API Resources to all responses | Medium — some endpoints return raw models | Medium | P1 |
| Immutable audit log (append-only store) | High — compliance requirement | Large | P1 |

---

## Completed Sprint Log

### Sprint 1 (2026-07-14)
**Built:** Full platform v1 — all core features listed in ✅ section above.
**Tests:** 54 tests, 91 passing.
**Commits:** `5c7d82f` (v1 build), `a1e597d` (IDE fixes), `ce62222` (blueprint doc)
