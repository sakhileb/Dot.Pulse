# Dot.Pulse API Standards
Version: 1.0 | REST API v1 | Classification: Internal

---

## Design Philosophy

Dot.Pulse's API is the primary integration surface for the Dot ecosystem. Every decision optimises for:

1. **Predictability** — Clients should be able to infer behaviour from naming alone
2. **Safety** — Every endpoint is authenticated, authorised, rate-limited, and validated
3. **Evolvability** — Breaking changes require version bumps, deprecation periods, and migration guides
4. **Discoverability** — OpenAPI spec is generated from code and always current

---

## Base URL

```
Production:  https://pulse.infodot.app/api/v1
Staging:     https://pulse-staging.infodot.app/api/v1
Local:       http://localhost:8000/api/v1
```

---

## Authentication

All endpoints require a Sanctum bearer token.

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

Tokens are created via the Ecosystem SSO handoff (`GET /auth/ecosystem?token=`) or directly via the API token management UI.

Token abilities used by this platform:
- `ecosystem:read` — SSO entry, read-only
- `api:full` — Full API access for integrations
- `api:read` — Read-only API access

---

## URL Structure

```
/api/v1/{resource}                    GET list
/api/v1/{resource}                    POST create
/api/v1/{resource}/{id}               GET show
/api/v1/{resource}/{id}               PUT/PATCH update
/api/v1/{resource}/{id}               DELETE destroy
/api/v1/{resource}/{id}/{action}      POST nested action
/api/v1/{parent}/{parentId}/{child}   GET nested resource
```

### Existing Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/feed` | Paginated AI-ranked post feed |
| GET | `/api/v1/posts/{id}` | Single post |
| POST | `/api/v1/posts` | Create post |
| DELETE | `/api/v1/posts/{id}` | Delete post |
| POST | `/api/v1/posts/{id}/react` | Toggle reaction |
| POST | `/api/v1/posts/{id}/report` | Report content |
| GET | `/api/v1/posts/{id}/comments` | Post comments |
| POST | `/api/v1/posts/{id}/comments` | Add comment |
| DELETE | `/api/v1/comments/{id}` | Delete comment |
| GET | `/api/v1/communities` | List communities |
| POST | `/api/v1/communities` | Create community |
| GET | `/api/v1/communities/{slug}` | Show community |
| POST | `/api/v1/communities/{slug}/join` | Join community |
| DELETE | `/api/v1/communities/{slug}/leave` | Leave community |
| POST | `/api/v1/users/{id}/follow` | Toggle follow |
| GET | `/api/v1/notifications` | List notifications |
| POST | `/api/v1/notifications/mark-read` | Mark all as read |
| POST | `/api/v1/notifications/{id}/read` | Mark one as read |
| GET | `/api/v1/notifications/unread-count` | Unread count |
| GET | `/api/v1/search?q=` | Search posts, communities, users |

### Planned Endpoints (Next Iteration)

| Method | Endpoint | Priority |
|---|---|---|
| GET | `/api/v1/users/{id}` | P1 |
| GET | `/api/v1/communities/{slug}/posts` | P1 |
| POST | `/api/v1/marketplace/{id}/install` | P1 |
| POST | `/api/v1/events/{id}/rsvp` | P1 |
| POST | `/api/v1/posts/{id}/poll/vote` | P1 |
| GET | `/api/v1/messages` | P2 |
| POST | `/api/v1/messages/{id}` | P2 |
| GET | `/api/v1/trending` | P2 |
| GET | `/api/v1/me` | P0 |
| PATCH | `/api/v1/me/profile` | P1 |

---

## Request Standards

### Headers (required on all requests)

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
X-Request-ID: {uuid}        # Client-generated, for tracing
```

### Pagination

All list endpoints use cursor-based pagination for feeds, offset for admin/search:

```json
{
  "data": [...],
  "links": {
    "first": "https://...",
    "last": "https://...",
    "prev": null,
    "next": "https://...?cursor=eyJpZCI6MTB9"
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 142
  }
}
```

Default page size: `20`. Maximum page size: `100`.

### Filtering

```
GET /api/v1/feed?type=question&community_id=5
GET /api/v1/communities?industry=Fleet+Management
GET /api/v1/search?q=automation&type=posts
```

Only whitelisted filter parameters are accepted. Unknown parameters are silently ignored (never cause errors).

### Sorting

```
GET /api/v1/feed?sort=created_at&direction=desc
```

Allowed sort fields are defined per-endpoint in the controller. Unknown sort fields are rejected with `422`.

---

## Response Standards

### Success

```json
{
  "data": {
    "id": 1,
    "type": "question",
    "body": "How do I integrate fleet tracking?",
    "status": "published",
    "author": {
      "id": 42,
      "name": "Jane Smith"
    },
    "created_at": "2026-07-14T10:30:00+00:00"
  }
}
```

Always use ISO 8601 for timestamps. Never return UNIX timestamps.

### Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "body": ["The body field is required."],
    "type": ["The selected type is invalid."]
  }
}
```

### HTTP Status Codes

| Code | Meaning | When to use |
|---|---|---|
| 200 | OK | Successful GET, PUT, PATCH, DELETE |
| 201 | Created | Successful POST that created a resource |
| 204 | No Content | Successful DELETE with no body |
| 400 | Bad Request | Malformed request syntax |
| 401 | Unauthorized | Missing or invalid auth token |
| 403 | Forbidden | Authenticated but not authorised |
| 404 | Not Found | Resource does not exist |
| 409 | Conflict | Duplicate resource (e.g., already joined) |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unexpected server error |
| 503 | Service Unavailable | Maintenance mode or dependency down |

---

## Rate Limiting

| Endpoint Group | Limit | Window |
|---|---|---|
| `POST /posts` | 10 requests | per hour per user |
| `POST /comments` | 40 requests | per hour per user |
| `POST /*/react` | 30 requests | per minute per user |
| `POST /*/follow` | 20 requests | per minute per user |
| `POST /*/report` | 5 requests | per hour per user |
| `GET /feed` | 120 requests | per minute per user |
| `GET /search` | 30 requests | per minute per user |
| Global (all endpoints) | 1000 requests | per hour per token |

Rate limit headers are included on every response:

```http
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 7
X-RateLimit-Reset: 1720958400
Retry-After: 3600  (only on 429)
```

---

## Idempotency

Mutating endpoints that may be retried (creates, payments, installs) must support idempotency keys:

```http
Idempotency-Key: {uuid}
```

The server stores the response for 24 hours. Duplicate requests with the same key return the original response without re-processing.

---

## Versioning Strategy

### Current: v1

- Breaking changes → bump to v2 (new URL prefix)
- Additive changes (new fields, new endpoints) → non-breaking, no version bump
- Field deprecation → add `deprecated: true` to OpenAPI spec, add `X-Deprecated-Field` header, maintain for 6 months

### Deprecation Process

1. Mark field/endpoint as deprecated in OpenAPI spec
2. Add `Sunset: {date}` header to affected responses
3. Add `Link: <migration-guide-url>; rel="deprecation"` header
4. Announce in release notes and API changelog
5. Remove after 6-month deprecation window

---

## OpenAPI Specification

The OpenAPI spec lives at:

```
GET /api/documentation          Human-readable (Swagger UI)
GET /api/documentation.json     Machine-readable (OpenAPI 3.1 JSON)
GET /api/documentation.yaml     Machine-readable (OpenAPI 3.1 YAML)
```

The spec is **generated from code annotations** — never edited by hand:

```bash
php artisan l5-swagger:generate
```

Every endpoint must include:
- Summary and description
- All parameters with types and validation rules
- All possible response codes with example bodies
- Security scheme reference
- Deprecation status if applicable

---

## Security Controls (per endpoint)

Every controller method must follow this guard order:

```php
public function store(CreatePostRequest $request): JsonResponse
{
    // 1. Authentication — handled by route middleware (auth:sanctum)
    // 2. Authorisation — explicit policy check
    $this->authorize('create', PulsePost::class);

    // 3. Validation — FormRequest already ran, $request->validated() is safe
    // 4. Rate limiting — inside Action class
    // 5. Business logic — delegated to Action
    $post = app(CreatePost::class)->handle(...$request->validated());

    // 6. Response — always through a Resource
    return response()->json(new PostResource($post), 201);
}
```

---

## Error Handling

### Custom Exceptions

Define typed exceptions for expected failures:

```php
// app/Exceptions/Pulse/RateLimitExceededException.php
class RateLimitExceededException extends HttpException
{
    public function __construct(string $action)
    {
        parent::__construct(429, "Rate limit exceeded for: $action");
    }
}
```

Register in `bootstrap/app.php` to map to JSON responses:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (RateLimitExceededException $e) {
        return response()->json(['message' => $e->getMessage()], 429);
    });
})
```

---

## Webhooks (Planned — P2)

Dot.Pulse will emit outgoing webhooks for key events:

| Event | Payload |
|---|---|
| `post.published` | Post resource + author |
| `comment.created` | Comment + post reference |
| `user.followed` | Follower + following |
| `marketplace.installed` | Item + installer |

Webhook delivery:
- Signed with HMAC-SHA256 (`X-Pulse-Signature` header)
- 3 retry attempts with exponential backoff
- Delivery log stored for 7 days
- Dead-letter queue for permanently failed deliveries
