# Dot.Pulse Database Standards
Version: 1.0 | PostgreSQL 16 | Classification: Internal

---

## Principles

1. **Schema is the contract** — The database schema is as important as the API contract. Breaking changes require migrations and backwards compatibility.
2. **Data integrity at the database level** — Constraints, foreign keys, and check constraints are not optional.
3. **Soft deletes everywhere** — Content is never permanently deleted without explicit legal or compliance reason.
4. **Audit trail by default** — `created_by`, `updated_by`, `deleted_by` on every table.
5. **No orphan records** — Every foreign key has a defined cascade rule.

---

## Migration Standards

### Naming Convention

```
YYYY_MM_DD_HHMMSS_verb_noun_table.php
```

Examples:
```
2026_07_14_120000_create_pulse_posts_table.php
2026_07_14_130000_add_ai_score_to_pulse_posts_table.php
2026_07_14_140000_add_index_to_pulse_posts_status_column.php
```

### Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pulse_posts', function (Blueprint $table) {
            // Primary key
            $table->id();
            $table->uuid('uuid')->unique()->index();

            // Tenant & ownership
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();

            // Content
            $table->string('type');
            $table->string('title', 200)->nullable();
            $table->text('body');

            // State
            $table->string('status')->default('pending');
            $table->boolean('is_pinned')->default(false);

            // Counters (denormalised for read performance)
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);

            // AI scoring
            $table->float('ai_relevance_score')->default(0.5);

            // Audit (soft delete)
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'community_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pulse_posts');
    }
};
```

---

## Standard Columns

These columns must appear on **every content-bearing table**:

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | Auto-increment PK |
| `uuid` | `uuid()->unique()` | Public-facing identifier, never expose `id` in APIs |
| `created_at` | `timestamps()` | Auto-managed |
| `updated_at` | `timestamps()` | Auto-managed |
| `deleted_at` | `softDeletes()` | Soft delete — never hard delete content |

For tables with user attribution:

| Column | Type | Notes |
|---|---|---|
| `created_by` | `foreignId` → users | Who created it |
| `updated_by` | `foreignId nullable` → users | Last updater |
| `deleted_by` | `foreignId nullable` → users | Who deleted it |

---

## Foreign Key Rules

All foreign keys must specify cascade behaviour explicitly:

| Relationship | Rule |
|---|---|
| User FK on user content | `cascadeOnDelete()` — delete user = delete their content |
| Community FK on posts | `nullOnDelete()` — delete community = posts become uncommunity'd |
| Parent FK on comments | `nullOnDelete()` — delete parent = replies become top-level |
| Team FK | `nullOnDelete()` — team deletion should not cascade to posts |
| Mandatory reference | `cascadeOnDelete()` — enrichment without post is useless |

Never use `restrictOnDelete()` unless there is an explicit business requirement that prevents deletion.

---

## Index Standards

### Required Indexes

```php
// Always index foreign keys (PostgreSQL does not auto-index FKs)
$table->index('user_id');
$table->index('community_id');

// Index columns used in WHERE clauses with cardinality above 100
$table->index('status');
$table->index(['status', 'created_at']);  // composite for common query

// Unique constraints (also creates index)
$table->unique('slug');
$table->unique(['user_id', 'community_id']);  // composite unique
```

### Partial Indexes (PostgreSQL-specific)

For tables with status columns, use partial indexes:

```php
// Add in a raw SQL migration for PostgreSQL performance
DB::statement("CREATE INDEX idx_posts_published ON pulse_posts (created_at DESC) WHERE status = 'published'");
```

---

## Enum Values

Use string enums, not integers. Store as `varchar` not `tinyint`.

```php
// ✅ Readable, searchable, debuggable
$table->string('status')->default('pending');
// CHECK constraint to enforce values (PostgreSQL)
DB::statement("ALTER TABLE pulse_posts ADD CONSTRAINT chk_status CHECK (status IN ('draft','pending','published','flagged','removed'))");

// ❌ Avoid — requires lookup table or enum mapping
$table->unsignedTinyInteger('status')->default(0);
```

---

## Counter Columns

Denormalised counters (`comments_count`, `reactions_count`) must be incremented and decremented atomically:

```php
// ✅ Atomic — uses SQL increment
$post->increment('comments_count');
$post->decrement('reactions_count');

// ❌ Race condition — read-modify-write
$post->update(['comments_count' => $post->comments_count + 1]);
```

Periodically reconcile counters via a scheduled command to correct drift from failed transactions.

---

## JSON Columns

Use `jsonb` in PostgreSQL (not `json`) for all JSON columns:

```php
$table->jsonb('tags')->nullable();
$table->jsonb('skills')->nullable();
$table->jsonb('meta')->nullable();
```

In Eloquent, always cast JSON columns:

```php
protected $casts = [
    'tags'   => 'array',
    'skills' => 'array',
    'meta'   => 'array',
];
```

---

## Query Patterns

### Avoid N+1

```php
// ❌ N+1 query
$posts = PulsePost::published()->get();
foreach ($posts as $post) {
    echo $post->author->name;  // New query per post
}

// ✅ Eager load
$posts = PulsePost::with('author:id,name')->published()->get();
```

### Limit Columns

```php
// ❌ Fetches all columns including large text/JSON fields
PulsePost::all();

// ✅ Only what you need
PulsePost::select(['id', 'type', 'title', 'status', 'created_at'])
    ->published()
    ->limit(20)
    ->get();
```

### Chunking for Bulk Operations

```php
// ✅ Memory-safe bulk processing
PulsePost::published()->chunkById(100, function ($posts) {
    foreach ($posts as $post) {
        // Process safely
    }
});
```

---

## Current Schema Map

```
authentication:
  users → teams (many-to-many via team_user)
  users → team_invitations
  personal_access_tokens (polymorphic)
  sessions, cache, jobs

pulse_core:
  pulse_profiles (1:1 user)
  communities (belongs to team)
  community_memberships (pivot: user ↔ community)
  community_rules
  pulse_followers (user ↔ user)
  pulse_posts (user, community, team)
  pulse_post_enrichments (1:1 post — AI results)
  pulse_comments (post, parent comment)
  pulse_reactions (polymorphic: post | comment)
  pulse_hashtags ↔ pulse_posts (via pulse_post_hashtag)

pulse_extended:
  pulse_polls (1:1 post)
  pulse_poll_votes (poll, user)
  pulse_media (polymorphic)
  pulse_conversations (pulse_conversation_user pivot)
  pulse_messages
  pulse_events (user, community)
  pulse_event_rsvps
  pulse_badges → pulse_user_badges
  pulse_reviews (polymorphic)
  pulse_marketplace_items → pulse_marketplace_installs
  pulse_reports (polymorphic)
  pulse_moderation_logs (polymorphic)
  pulse_knowledge_nodes → pulse_knowledge_edges
  notifications (Laravel standard)
```

---

## PostgreSQL-Specific Features to Use

| Feature | Use case |
|---|---|
| `jsonb` with GIN index | Tag search, JSON querying |
| `tsvector` + `tsquery` | Full-text search (until Scout/Meilisearch is configured) |
| Partial indexes | `WHERE status = 'published'` query optimisation |
| `UNLOGGED TABLE` | Temporary session/cache tables (not for Pulse data) |
| `pg_trgm` extension | ILIKE similarity search |
| Row-level security | Multi-tenant isolation at DB level (Phase 2) |

---

## Migration Safety Rules

| Rule | Reason |
|---|---|
| Never drop columns without deprecation period | Deployed app may still read old column |
| Never rename columns | Use add-new + migrate-data + drop-old pattern |
| Never change column types directly | May lock table on large datasets |
| Always add new nullable columns | Non-nullable additions break existing rows |
| Test migrations on production-sized datasets | Performance surprises hit production, not dev |
| Always define `down()` | Rollbacks must work |

---

## Adding a New Domain (Checklist)

When adding a new feature domain:

- [ ] Migration created with all standard columns
- [ ] Foreign keys with explicit cascade rules
- [ ] Indexes on all FK columns and query columns
- [ ] Soft deletes on content tables
- [ ] `uuid` column added
- [ ] Eloquent casts defined for all JSON/bool/date columns
- [ ] `HasFactory` trait added
- [ ] Factory created with realistic fake data
- [ ] Seeder updated to include demo records
- [ ] Model relationships fully defined
- [ ] Policy created and registered in AppServiceProvider
