# Dot.Pulse — Project Guidelines for GitHub Copilot

## What is this project?

Dot.Pulse is an **AI-powered community intelligence platform** built with Laravel 13, Livewire 3, PostgreSQL, and Laravel Reverb. It is part of the Dot ecosystem (`pulse.infodot.app`). It is NOT a generic social media app — it is a professional enterprise community platform for sharing knowledge, discovering AI agents, and building reputation within the Dot ecosystem.

## Documentation Library

Before writing any code, check whether a doc governs that area. All docs are in `docs/`.

| Task | Read this first |
|---|---|
| Writing a new PHP class, method, or component | `docs/CODING_STANDARDS.md` |
| Adding or modifying an API endpoint | `docs/API_STANDARDS.md` |
| Creating or modifying a database migration | `docs/DATABASE_STANDARDS.md` |
| Writing a test | `docs/TESTING_STANDARDS.md` |
| Anything involving AI / Claude / moderation | `docs/AI_GOVERNANCE.md` |
| Building a Livewire component or Blade view | `docs/UI_DESIGN_SYSTEM.md` |
| Designing user interactions or flows | `docs/UX_PRINCIPLES.md` |
| Writing UI copy, labels, errors, empty states | `docs/CONTENT_GUIDELINES.md` |
| Privacy, GDPR, POPIA, data handling | `docs/PRIVACY_FRAMEWORK.md` |
| Logging, metrics, monitoring | `docs/MONITORING_OBSERVABILITY.md` |
| CI/CD, GitHub Actions, deployment | `docs/DEVSECOPS_PIPELINE.md` |
| Incident or production issue | `docs/INCIDENT_RESPONSE.md` |
| Multi-tenant features | `docs/MULTI_TENANT_ARCHITECTURE.md` |
| Planning what to build next | `docs/FEATURE_ROADMAP.md` |
| Enterprise security controls | `docs/ENTERPRISE_VENDOR_SECURITY_BLUEPRINT.md` |
| WCAG / accessibility | `docs/ACCESSIBILITY_STANDARDS.md` |
| Performance, budgets, Core Web Vitals | `docs/PERFORMANCE_BUDGET.md` |
| Mobile / responsive layouts | `docs/RESPONSIVE_DESIGN_GUIDE.md` |
| User onboarding flows | `docs/ONBOARDING_FLOW.md` |
| Email templates and transactional email | `docs/EMAIL_TEMPLATES.md` |
| Vendor / enterprise questionnaire evidence | `docs/VENDOR_QUESTIONNAIRE.md` |

## Architecture Rules

**Follow this pattern strictly — no exceptions:**

```
HTTP Request → Controller (thin) → Action (business logic) → Service (if complex)
                                        ↓
                                    Event::dispatch()
                                        ↓
                            Listener → Job (async) → Notification
```

- **Controllers** — receive input, call one Action, return one Resource. No DB calls, no `if` chains.
- **Actions** — `app/Actions/Pulse/`. One public `handle()` method. Named after what they do. No HTTP objects.
- **Services** — stateless, injectable. Used when logic needs reuse across multiple Actions.
- **Livewire Components** — call Actions for mutations. Use `Auth::user()` (never `auth()->user()`). Use `#[Computed]` for derived data.
- **Models** — relationships, scopes, casts only. Zero business logic.
- **Policies** — one per model, registered in `AppServiceProvider`. Always `$this->authorize()` in controllers.

## Code Style Rules

- Use `Auth::user()` and `Auth::id()` — never `auth()->user()` or `auth()->id()`
- Use `Str::limit()` not `\Str::limit()` — import `use Illuminate\Support\Str;`
- Use `Storage::disk()` with `/** @var FilesystemAdapter $disk */` for IDE resolution
- All JSON columns must have `'column' => 'array'` in `$casts`
- All boolean columns must be cast
- All models must define `$table` explicitly
- Soft deletes (`SoftDeletes`) on every content model
- `HasFactory` on every model used in tests or seeding
- PHPStan Level 9 must pass — all return types and property types declared

## Current Stack

- **Framework:** Laravel 13, PHP 8.3
- **Frontend:** Livewire 3, Alpine.js 3, Tailwind CSS (via Vite), Material Symbols Rounded icons
- **Database:** PostgreSQL (production), SQLite (local/tests)
- **Queue:** Database driver (`QUEUE_CONNECTION=database`)
- **Realtime:** Laravel Reverb + Laravel Echo
- **AI:** Anthropic Claude via `AiModerationService` (async via `EnrichPost` job)
- **Auth:** Jetstream teams + Fortify + Sanctum

## Design System

- Background: `#09090b` (page) / `#141416` (cards) / `#0d0d10` (sidebar)
- Accent: `#c084fc` (purple)
- Text: `#f4f4f5` (primary) / `#a1a1aa` (secondary) / `#71717a` (muted) / `#52525b` (faint)
- Fonts: Syne (headings), Inter (body), JetBrains Mono (metrics/code)
- Components: `.dot-card`, `.dot-btn`, `.dot-btn-primary`, `.dot-btn-ghost`, `.dot-input`, `.dot-badge`
- **Never invent new colour values.** Only use tokens from `docs/UI_DESIGN_SYSTEM.md`.

## Security Rules

- Every API endpoint must have: auth middleware, policy check, FormRequest validation, rate limiting
- Rate limiters are defined in `AppServiceProvider` — use `RateLimiter::attempt()` in components/actions
- Never pass raw user input directly to AI prompts — sanitise and truncate first
- Never store secrets in code, logs, or JS bundles
- File uploads: MIME validation + size check — virus scanning required before production

## Testing Rules

- Every Action needs a Feature test covering: happy path, policy violation, validation failure
- Use `Queue::fake()` when testing post creation (avoids real AI calls)
- Use `Notification::fake()` when testing notification dispatch
- Use `RefreshDatabase` trait on all Feature tests
- Minimum 95% coverage — enforced in CI

## Definition of Done

A feature is NOT complete unless:
- [ ] Policy implemented and tested
- [ ] Tests written (happy path + sad path)
- [ ] Rate limiting applied if user-triggered
- [ ] All user-facing strings follow `docs/CONTENT_GUIDELINES.md`
- [ ] New Livewire views use only design tokens from `docs/UI_DESIGN_SYSTEM.md`
- [ ] Accessible (ARIA labels, contrast, keyboard nav per `docs/ACCESSIBILITY_STANDARDS.md`)
- [ ] Mobile layout correct per `docs/RESPONSIVE_DESIGN_GUIDE.md`

## Build & Test Commands

```bash
composer install
npm install && npm run build
php artisan migrate
php artisan db:seed
./vendor/bin/phpunit                    # Run all tests
./vendor/bin/phpunit tests/Feature/Pulse/  # Run Pulse tests only
./vendor/bin/phpstan analyse --level=9 # Static analysis
./vendor/bin/pint                       # Fix code style
php artisan queue:work                  # Start queue worker
php artisan pulse:trending              # Recalculate trending hashtags
```

Admin login for local dev: `admin@pulse.test` / `password`
