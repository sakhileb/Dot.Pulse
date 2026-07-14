# Dot.Pulse Coding Standards
Version: 1.0 | Laravel 13 + PHP 8.3 | Classification: Internal

---

## Core Principle

> Every feature is an Action. Every side effect is an Event. Every async task is a Job. Every response is a Resource.

Controllers and Livewire components are **orchestrators only** — they receive input, call one Action, return a response. No business logic lives outside of Actions and Services.

---

## Directory Conventions

```
app/
├── Actions/Pulse/          # Single-responsibility write operations
├── Services/               # Stateless, injectable business services
├── Jobs/                   # Async queued tasks
├── Events/                 # Domain events (broadcast + internal)
├── Listeners/              # Event side effects
├── Notifications/          # User-facing notifications
├── Policies/               # Authorization decisions
├── Models/                 # Eloquent models (data + relationships only)
├── Http/
│   ├── Controllers/        # Thin route handlers
│   └── Requests/           # Validation (Form Requests)
├── Livewire/Pulse/         # UI components (thin — call Actions)
├── Console/Commands/       # CLI commands
└── Exceptions/             # Custom exception types
```

---

## Actions

Every user-initiated write must go through an Action class.

### Rules

- One public `handle()` method
- Named after what it does: `CreatePost`, `FollowUser`, `MarkSolution`
- No HTTP-specific code (`Request`, `Response`, redirect)
- Throws typed exceptions on failure
- Returns the primary entity created or modified
- Rate limiting applied inside the Action, not in the controller

### Template

```php
<?php

namespace App\Actions\Pulse;

use App\Models\PulsePost;
use App\Models\PulseProfile;
use App\Services\BadgeAwarder;
use Illuminate\Support\Facades\Auth;

class CreatePost
{
    public function __construct(
        private readonly BadgeAwarder $badges,
    ) {}

    public function handle(
        int    $userId,
        string $type,
        string $body,
        string $title = '',
        ?int   $communityId = null,
    ): PulsePost {
        // 1. Validate business rules (not HTTP input validation)
        abort_if($type === 'announcement' && ! $this->isAdmin($userId), 403);

        // 2. Persist
        $post = PulsePost::create([...]);

        // 3. Side effects via events
        PostCreated::dispatch($post);

        // 4. Award points / badges
        $profile = PulseProfile::where('user_id', $userId)->first();
        $profile?->addPoints(5);
        $this->badges->checkAll(Auth::user());

        return $post;
    }
}
```

---

## Controllers

### Rules

- Inject only what's needed
- One action per method
- Return an API Resource or redirect
- No `if` chains — use policies and actions
- No DB calls — delegate to Actions and Queries

### Template

```php
public function store(CreatePostRequest $request): JsonResponse
{
    $post = app(CreatePost::class)->handle(
        userId:      $request->user()->id,
        type:        $request->type,
        body:        $request->body,
        communityId: $request->community_id,
    );

    return response()->json(new PostResource($post), 201);
}
```

---

## Livewire Components

### Rules

- Call Actions for all mutations
- Use `#[Computed]` for derived data (lazy-loaded, cached per render)
- Use `WithPagination` for any list longer than 20 items
- Rate-limit via `RateLimiter::attempt()` inside the method
- Use `Auth::user()` / `Auth::id()` — never `auth()->user()`
- Dispatch Livewire events (`$this->dispatch('event-name')`) to signal other components
- Never query the DB directly in a component method — use a Query class or Model scope

### Template

```php
public function publish(): void
{
    if (! RateLimiter::attempt('pulse-post:' . Auth::id(), 10, fn () => true)) {
        $this->addError('body', 'Too many posts. Wait a moment.');
        return;
    }

    $this->validate();

    app(CreatePost::class)->handle(
        userId: Auth::id(),
        type:   $this->type,
        body:   $this->body,
    );

    $this->reset(['body', 'title']);
    $this->dispatch('post-created');
}
```

---

## Models

### Rules

- Fillable arrays must be exhaustive
- All JSON columns must be cast (`'tags' => 'array'`)
- All boolean columns must be cast
- All date columns must be cast to `datetime`
- No business logic in models — only relationships, scopes, and casts
- All models must define `$table` explicitly
- Soft deletes (`SoftDeletes`) on any content model
- `HasFactory` on any model that needs seeding or testing

### Forbidden in Models

```php
// ❌ Never in a model
public function publish(): void
{
    $this->update(['status' => 'published']);
    event(new PostPublished($this));   // ← belongs in an Action
    Mail::to($this->author)->send(...);
}
```

### Allowed in Models

```php
// ✅ Scopes
public function scopePublished(Builder $query): Builder
{
    return $query->where('status', 'published');
}

// ✅ Accessors
public function getIsExpiredAttribute(): bool
{
    return $this->expires_at?->isPast() ?? false;
}

// ✅ Relationships
public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

---

## Policies

Every resource must have a Policy registered in `AppServiceProvider`.

```php
// Naming: {Model}Policy
// Method names match ability: view, viewAny, create, update, delete, restore, forceDelete

public function update(User $user, PulsePost $post): bool
{
    return $post->user_id === $user->id;
}

public function delete(User $user, PulsePost $post): bool
{
    return $post->user_id === $user->id || $this->isModerator($user);
}
```

Never perform authorization in controllers directly — always use `$this->authorize()` or `Gate::authorize()`.

---

## Form Requests

Every API endpoint must use a `FormRequest`. Never validate in controllers.

```php
class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'type'         => ['required', Rule::in(PulsePost::$types)],
            'body'         => ['required', 'string', 'min:10', 'max:10000'],
            'title'        => ['nullable', 'string', 'max:200'],
            'community_id' => ['nullable', 'exists:communities,id'],
        ];
    }
}
```

---

## API Resources

All API responses must use `JsonResource`. Never return Eloquent models directly.

```php
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'title'      => $this->title,
            'body'       => $this->body,
            'status'     => $this->status,
            'author'     => new UserResource($this->whenLoaded('author')),
            'community'  => new CommunityResource($this->whenLoaded('community')),
            'enrichment' => new EnrichmentResource($this->whenLoaded('enrichment')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

---

## Database Queries

### Rules

- All raw queries must use parameter binding — never string interpolation
- Use `->select()` to limit columns on large tables
- Never use `->get()` without a `->limit()` or pagination on unbounded tables
- Use `->with()` to eager-load — never lazy-load in loops (N+1)
- Use `->chunkById()` for bulk processing
- Wrap multi-step writes in `DB::transaction()`

### Detecting N+1

Enable query logging in tests:

```php
DB::enableQueryLog();
// ... actions
$queries = DB::getQueryLog();
$this->assertLessThan(5, count($queries), 'Too many queries — check for N+1');
```

---

## Naming Conventions

| Context | Convention | Example |
|---|---|---|
| Class | PascalCase | `CreatePost` |
| Method | camelCase | `markSolution()` |
| Variable | camelCase | `$communityId` |
| Database column | snake_case | `community_id` |
| Route name | dot.notation | `posts.show` |
| Livewire event | kebab-case | `post-created` |
| Blade template | kebab-case | `create-post.blade.php` |
| Config key | dot.notation | `services.anthropic.key` |
| Queue name | kebab-case | `pulse-moderation` |
| Cache key | colon-separated | `pulse:trending_hashtags` |

---

## PHPStan / Larastan

Run at Level 9 before every merge:

```bash
./vendor/bin/phpstan analyse --level=9
```

Rules enforced:
- All method return types declared
- All property types declared
- No `mixed` return types without justification
- All nullable parameters annotated with `?`
- `Auth::user()` usage typed with `@var User $user`

---

## Git Workflow

### Commit Message Format

```
type(scope): short description

Detailed explanation of WHY (not what).
Include affected components, test results, security notes.

Refs: #issue-number
```

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `security`, `perf`

### Branch Naming

```
feat/pulse-poll-voting
fix/messaging-pivot-timestamps
security/rate-limit-comments
refactor/extract-notification-service
```

### PR Requirements

- All CI checks green
- 2 peer reviews
- Security review for any auth/permission change
- Self-tested in a staging environment
- Description includes "How to test" steps
