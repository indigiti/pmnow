# API v1 + Content Hub API

All PHP JSON responses use:

```json
{
  "data": {},
  "meta": {},
  "errors": []
}
```

## Public product API

```text
GET  /api/v1/health
GET  /api/v1/feed
GET  /api/v1/stories/{uuid}
GET  /api/v1/search?q=...
GET  /api/v1/reels
GET  /api/v1/live/{uuid}/updates
GET  /api/v1/live/{uuid}/stream
POST /api/v1/live/{uuid}/demo-update
GET  /api/v1/me
POST /api/v1/stories/{uuid}/bookmark
POST /api/v1/stories/{uuid}/follow
```

## M4/M5 Content Hub orchestration API

```text
GET  /api/admin/engine/health
GET  /api/admin/providers
GET  /api/admin/sources
POST /api/admin/sources
POST /api/admin/sources/{uuid}/test
POST /api/admin/sources/{uuid}/sync
GET  /api/admin/content
POST /api/admin/content/import
POST /api/admin/content/{uuid}/analyze
GET  /api/admin/clusters
```

### Create source

```json
{
  "provider": "wordpress",
  "name": "Pune Mirror WordPress",
  "handle": "punemirror.com",
  "enabled": true,
  "sync_enabled": true,
  "sync_interval": 120,
  "classification_mode": "review",
  "settings": {
    "site_url": "https://example.com"
  },
  "credential_env": {}
}
```

Credential values are not persisted in the source record. `credential_env` maps a logical secret name to an environment variable.

### Manual import

```json
{
  "publish": true,
  "content": {
    "external_id": "desk-2026-09-23-001",
    "content_type": "article",
    "title": "Headline",
    "body": "Full text",
    "published_at": "2026-09-23T17:00:00+05:30",
    "media": [
      {"type": "image", "url": "https://..."}
    ]
  }
}
```

Import flow:

```text
normalize → persist content → intelligence → duplicate/related → story cluster → optional Story publish
```

## Private FastAPI engine

Default bind: `127.0.0.1:8788`

```text
GET  /health
GET  /v1/providers
POST /v1/source/test
POST /v1/source/sync
POST /v1/content/normalize
POST /v1/content/analyze
POST /v1/content/duplicate
POST /v1/content/related
```

The FastAPI service is an internal service and should not be directly internet-exposed in production.

# M6 Newsroom API additions

## Admin session

```text
POST /api/admin/session
POST /api/admin/logout
GET  /api/admin/me
```

## Editorial workflow

```text
GET  /api/admin/editorial/inbox?status=ready
POST /api/admin/editorial/{content_uuid}/action
POST /api/admin/editorial/bulk
```

Action body:

```json
{
  "action": "approve",
  "reason": "Verified by city desk",
  "changes": {}
}
```

Supported actions: `approve`, `publish`, `review`, `reject`, `archive`, `hide`, `mark_duplicate`, `ready`, `edit`.

## Source operations

```text
GET    /api/admin/sources
POST   /api/admin/sources
PATCH  /api/admin/sources/{uuid}
DELETE /api/admin/sources/{uuid}
POST   /api/admin/sources/{uuid}/test
POST   /api/admin/sources/{uuid}/sync
```

## Scheduler / worker

```text
GET  /api/admin/jobs
POST /api/admin/jobs/scheduler-tick
POST /api/admin/jobs/run-next
```

## Search maintenance

```text
POST /api/admin/search/rebuild
```

Public search remains:

```text
GET /api/v1/search?q=metro&limit=20
```

## Reader notifications

```text
GET  /api/v1/notifications
POST /api/v1/notifications/{uuid}/read
```

# M7 additions

All state-changing browser endpoints require `X-CSRF-Token`, obtained from the page `<meta name="csrf-token">` element.

```text
POST /api/admin/sources/{id}/credentials
GET  /api/admin/system/health
GET  /api/admin/errors
POST /api/admin/errors/{id}/resolve
```

Credential payload:

```json
{"credentials":{"access_token":"..."}}
```

The response contains masked metadata only; plaintext credential values are never returned.

HTTP `419` indicates CSRF failure and `429` indicates rate limiting.
