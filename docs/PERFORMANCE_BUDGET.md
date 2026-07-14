# Dot.Pulse Performance Budget
Version: 1.0 | Core Web Vitals | Classification: Internal

---

## Budget Overview

A performance budget defines the maximum acceptable cost of any page or interaction. Budgets are enforced in CI — a build that violates a budget fails.

---

## Core Web Vitals Targets

| Metric | Target (Good) | Acceptable | Fail |
|---|---|---|---|
| LCP (Largest Contentful Paint) | < 1.5s | < 2.5s | > 2.5s |
| FID / INP (Interaction to Next Paint) | < 100ms | < 200ms | > 200ms |
| CLS (Cumulative Layout Shift) | < 0.05 | < 0.10 | > 0.10 |
| TTFB (Time to First Byte) | < 200ms | < 500ms | > 800ms |
| FCP (First Contentful Paint) | < 1.0s | < 1.8s | > 1.8s |

---

## Lighthouse Score Targets

| Category | Target | Minimum |
|---|---|---|
| Performance | ≥ 95 | 85 |
| Accessibility | ≥ 95 | 90 |
| Best Practices | ≥ 95 | 90 |
| SEO | ≥ 90 | 80 |

Run Lighthouse against these pages minimum: `/` (welcome), `/dashboard`, `/communities`, `/posts/{id}`.

---

## API Response Time Budget

| Endpoint Category | p50 | p95 | p99 |
|---|---|---|---|
| Feed (GET /api/v1/feed) | 80ms | 250ms | 500ms |
| Post detail | 50ms | 150ms | 300ms |
| Search | 60ms | 200ms | 400ms |
| Post creation | 100ms | 300ms | 600ms |
| Comment creation | 80ms | 200ms | 400ms |
| Notifications | 40ms | 150ms | 300ms |

---

## Database Query Budget

| Context | Limit | Notes |
|---|---|---|
| Queries per page load | ≤ 10 | Use eager loading |
| Single query execution | ≤ 50ms | Add index if exceeded |
| Aggregation queries | ≤ 100ms | Cache results |
| Full-text search | ≤ 100ms | Meilisearch required at scale |

---

## Asset Budget

| Asset | Limit | Current |
|---|---|---|
| CSS bundle (gzipped) | ≤ 15 KB | 11.79 KB ✅ |
| JS bundle (gzipped) | ≤ 25 KB | 18.61 KB ✅ |
| Total HTML page weight | ≤ 150 KB | — |
| Images (per page) | ≤ 500 KB total | — |
| Fonts | ≤ 50 KB | Google Fonts CDN |
| Third-party scripts | ≤ 3 on critical path | Tailwind CDN (dev only) |

**Critical issue:** The layout uses `<script src="https://cdn.tailwindcss.com">` — this is a 100+ KB CDN script loaded on every page. It must be removed before production and replaced with the Vite-compiled Tailwind CSS.

---

## Livewire-Specific Budget

| Concern | Limit | Implementation |
|---|---|---|
| Computed properties (DB queries) per render | ≤ 5 | Use `#[Computed]` (cached per render) |
| Data transferred per Livewire update | ≤ 50 KB | Don't pass large models to components |
| Components on a single page | ≤ 5 per page | Avoid nesting deeply |
| `wire:poll` interval | ≥ 30s | Shorter intervals kill server |
| Paginate all lists | 20 items/page | Never `->get()` without limit |

---

## Image Optimisation Rules

| Requirement | Rule |
|---|---|
| Format | WebP for photos, SVG for icons/logos |
| Max avatar size | 200×200px, < 50 KB |
| Max cover photo size | 1200×300px, < 200 KB |
| Max post attachment | 2000px wide, < 2 MB |
| Lazy loading | `loading="lazy"` on all images below fold |
| Explicit dimensions | Always set `width` and `height` (prevents CLS) |
| Thumbnail generation | Server-side on upload — never send full-res to browser |

---

## Font Loading Strategy

Current (development):

```html
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800
           &family=Inter:wght@400;500;600
           &family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
```

Production target:

```html
<!-- Preconnect early -->
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Preload critical fonts -->
<link rel="preload" as="font" type="font/woff2"
      href="/fonts/syne-700.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2"
      href="/fonts/inter-400.woff2" crossorigin>

<!-- Self-host fonts for privacy + performance -->
```

Self-hosting fonts removes a DNS lookup, prevents layout shift, and avoids GDPR implications of Google Fonts.

---

## Server-Side Caching Strategy

| Data | Cache Duration | Key Pattern |
|---|---|---|
| Trending hashtags | 5 minutes | `pulse:trending_hashtags` |
| Community list (public) | 2 minutes | `pulse:communities:public` |
| User profile (public) | 1 minute | `pulse:profile:{user_id}` |
| Post enrichment | Permanent (immutable) | `pulse:enrichment:{post_id}` |
| Feed (per user) | 30 seconds | `pulse:feed:{user_id}:{page}` |
| Unread notification count | 30 seconds | `pulse:notifications:unread:{user_id}` |
| Marketplace items | 1 minute | `pulse:marketplace:{category}:{page}` |

Use `Cache::remember($key, $seconds, fn () => ...query...)` in all `#[Computed]` properties that hit the DB.

---

## Removing the Tailwind CDN (Critical)

The layout currently includes:

```html
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { corePlugins: { preflight: false } }</script>
```

This is **development-only** and must be replaced for production. The Vite build already compiles `resources/css/app.css` — the design system classes must be migrated to that file.

**Migration steps:**

1. Move all CSS from the `<style>` block in `layouts/app.blade.php` into `resources/css/app.css`
2. Remove the CDN script tags
3. Add `@vite(['resources/css/app.css', 'resources/js/app.js'])` to the layout head
4. Test all pages after migration
5. Run Lighthouse to confirm no regressions

---

## Performance Monitoring

### Local Development

```bash
# Check bundle sizes after build
npm run build -- --report

# Lighthouse CLI (install globally)
npm install -g lighthouse
lighthouse http://localhost:8000/dashboard --output=html --output-path=lighthouse-report.html
```

### CI Performance Gate (to implement)

```yaml
# Add to ci.yml
performance:
  runs-on: ubuntu-latest
  steps:
    - uses: treosh/lighthouse-ci-action@v10
      with:
        urls: |
          http://localhost:8000/
          http://localhost:8000/dashboard
        budgetPath: ./lighthouse-budget.json

# lighthouse-budget.json
[{
  "path": "/*",
  "timings": [
    { "metric": "first-contentful-paint", "budget": 1800 },
    { "metric": "largest-contentful-paint", "budget": 2500 },
    { "metric": "cumulative-layout-shift",  "budget": 0.1 }
  ],
  "resourceSizes": [
    { "resourceType": "script",     "budget": 60 },
    { "resourceType": "stylesheet", "budget": 20 }
  ]
}]
```

---

## Database Query Profiling

Enable in `local` and `staging` environments:

```php
// In AppServiceProvider::boot() for local/staging only
if (app()->environment('local', 'staging')) {
    DB::listen(function ($query) {
        if ($query->time > 100) {
            Log::warning('Slow query detected', [
                'sql'      => $query->sql,
                'time_ms'  => $query->time,
                'bindings' => $query->bindings,
            ]);
        }
    });
}
```

Any query over 50ms in production should be investigated within 1 sprint.

---

## N+1 Query Detection

Install Telescope (staging) or use this test helper:

```php
// In Feature tests — detect N+1 automatically
public function test_feed_has_no_n_plus_one_queries(): void
{
    // Create 20 posts with authors and enrichments
    PulsePost::factory(20)->create(['status' => 'published']);

    $queryCount = 0;
    DB::listen(fn () => $queryCount++);

    $this->actingAs($this->user)->getJson('/api/v1/feed');

    // 20 posts should not = 20+ author queries
    $this->assertLessThan(10, $queryCount, "N+1 detected: {$queryCount} queries for 20 posts");
}
```
