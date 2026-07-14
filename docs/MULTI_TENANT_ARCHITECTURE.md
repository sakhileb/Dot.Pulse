# Dot.Pulse Multi-Tenant Architecture
Version: 1.0 | Phase 2 Implementation Guide | Classification: Internal

---

## Current State

Dot.Pulse currently implements **team-based isolation** via Laravel Jetstream teams. A user belongs to one or more teams, and content (posts, communities) can be team-scoped.

This document defines the path from the current state to **full multi-tenant isolation** suitable for enterprise customers who require strict data separation.

---

## Tenancy Model

```
Dot Ecosystem Tenant (top level)
    └─ Organization / Team
            └─ Users (members)
                    └─ Content (posts, communities, marketplace, etc.)
```

Each tenant:
- Has its own isolated data scope
- Can have private communities visible only to their members
- Has customisable feature flags and subscription limits
- Receives its own audit trail
- Can be independently deleted without affecting other tenants

---

## Tenant Identification

Every request must identify its tenant from one of these sources (in priority order):

1. **Subdomain** — `acmecorp.pulse.infodot.app` → tenant `acmecorp`
2. **Custom domain** — `community.acmecorp.com` → tenant lookup
3. **JWT/Token claim** — `tenant_id` in Sanctum token payload
4. **Request header** — `X-Tenant-ID` (for machine-to-machine)

### Tenant Resolution Middleware

```php
// app/Http/Middleware/ResolveTenant.php
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        // Bind tenant to container for the request lifecycle
        app()->instance('tenant', $tenant);
        app()->instance('tenant_id', $tenant->id);

        // Set tenant context for logging
        Log::withContext(['tenant_id' => $tenant->id, 'tenant_slug' => $tenant->slug]);

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Team
    {
        // From subdomain
        $host   = $request->getHost();
        $parts  = explode('.', $host);
        $slug   = count($parts) >= 3 ? $parts[0] : null;

        if ($slug && $slug !== 'www') {
            return Team::where('slug', $slug)->first();
        }

        // From token
        if ($token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);
            return $accessToken?->tokenable?->currentTeam;
        }

        return null;
    }
}
```

---

## Global Scope Enforcement

Every model that contains tenant-specific data **must** have a global scope enforcing tenant isolation.

### TenantScope

```php
// app/Models/Scopes/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->has('tenant_id')) {
            $builder->where($model->getTable() . '.team_id', app('tenant_id'));
        }
    }
}
```

### Apply to Models

```php
// app/Models/PulsePost.php
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope());

    // Automatically set team_id on creation
    static::creating(function (PulsePost $post) {
        if (app()->has('tenant_id') && ! $post->team_id) {
            $post->team_id = app('tenant_id');
        }
    });
}
```

**Models requiring TenantScope:**
- `PulsePost`
- `Community`
- `PulseEvent`
- `PulseMarketplaceItem` (tenant-published items)
- `PulseConversation`
- Any future content model

**Models NOT requiring TenantScope:**
- `User` (users can belong to multiple tenants)
- `PulseFollower` (cross-tenant follows may be allowed)
- `PulseBadge` (platform-wide)
- `PulseHashtag` (platform-wide trending)

---

## Bypassing Tenant Scope

For administrative and platform-level operations:

```php
// ✅ Safe — explicitly disabling scope for admin queries
PulsePost::withoutGlobalScope(TenantScope::class)->where('status', 'pending')->get();

// ✅ For console commands
Community::withoutGlobalScopes()->chunk(100, fn($communities) => ...);

// ❌ Never in API controllers or Actions — always respect tenant scope
```

---

## Tenant-Scoped Policies

Policies must verify tenant membership, not just ownership:

```php
// app/Policies/PostPolicy.php
public function view(?User $user, PulsePost $post): bool
{
    // Public posts — visible to anyone in the same tenant
    if ($post->status === 'published' && $this->sameTenant($user, $post)) {
        return true;
    }

    return $post->user_id === $user?->id;
}

private function sameTenant(?User $user, PulsePost $post): bool
{
    if (! app()->has('tenant_id')) {
        return true; // No tenant context — allow (dev/testing)
    }

    return $post->team_id === app('tenant_id');
}
```

---

## Enterprise Private Communities

Enterprise tenants get private communities that are completely invisible outside their tenant:

```php
// Visibility: 'public' | 'private' | 'enterprise'
// 'enterprise' = visible only within the tenant's team, requires tenant scope
```

Community query always includes tenant filtering:

```php
// Always applied by TenantScope
Community::where('visibility', 'public')->get();

// For enterprise communities — must be in same tenant
Community::where('visibility', 'enterprise')->get();  // TenantScope already limits this
```

---

## Tenant Database Isolation Options

### Option A: Row-Level Isolation (Current)

All tenants share the same tables. `team_id` column separates data. TenantScope enforces at Eloquent level.

**Pros:** Simple, scales well, no schema duplication  
**Cons:** Requires diligent scope application; a scope bug could expose cross-tenant data

**This is the current implementation.** Suitable for most customers.

### Option B: Schema-Per-Tenant (Phase 3)

Each tenant gets its own PostgreSQL schema. Connection switches on request start.

```php
// Connection switch example (Stancl/Tenancy pattern)
DB::statement("SET search_path TO tenant_{$tenantId}");
```

**Pros:** Strong isolation, tenant data can be independently backed up  
**Cons:** Migration complexity multiplied by tenant count, no cross-tenant queries

**Recommended for:** Enterprise customers with strict data residency requirements.

### Option C: Database-Per-Tenant (Phase 4)

Each enterprise tenant gets their own database instance.

**Pros:** Maximum isolation, independent scaling, easiest compliance evidence  
**Cons:** Highest operational complexity and cost

---

## Tenant Feature Flags

Enterprise tenants may have different features enabled:

```php
// pulse_tenant_features table (to be created)
// tenant_id | feature | enabled | config (jsonb)

// Usage in controllers/Livewire
if (TenantFeatures::enabled('private_communities')) {
    // Show private community option
}

if (TenantFeatures::enabled('ai_moderation')) {
    EnrichPost::dispatch($post);
} else {
    $post->update(['status' => 'published']);  // Auto-approve for tenants without AI
}
```

---

## Tenant Isolation Testing

Every feature must include a cross-tenant isolation test:

```php
public function test_tenant_b_cannot_see_tenant_a_posts(): void
{
    $tenantA = Team::factory()->create();
    $tenantB = Team::factory()->create();

    $userA = User::factory()->create(['current_team_id' => $tenantA->id]);
    $userB = User::factory()->create(['current_team_id' => $tenantB->id]);

    // Create post as Tenant A
    $post = PulsePost::factory()->create([
        'team_id'  => $tenantA->id,
        'user_id'  => $userA->id,
        'status'   => 'published',
    ]);

    // Tenant B should not see Tenant A's post in the feed
    $this->actingAs($userB)
        ->getJson('/api/v1/feed')
        ->assertJsonMissing(['id' => $post->id]);
}
```

---

## Migration Path from Current State

| Step | Action | Effort |
|---|---|---|
| 1 | Add `TenantScope` class | Small |
| 2 | Apply scope to all content models | Medium |
| 3 | Add `ResolveTenant` middleware | Small |
| 4 | Update all Policies to check tenant | Medium |
| 5 | Add cross-tenant isolation tests | Medium |
| 6 | Add `pulse_tenant_features` table | Small |
| 7 | Implement tenant feature flag service | Medium |
| 8 | Add tenant management admin UI | Large |
| 9 | Add subdomain routing | Medium |
| 10 | Tenant onboarding workflow | Large |
