# Architecture Freeze — M1 through M5

```text
Mobile Web UI
Tailwind / Alpine / GSAP / Vite
          │
          ▼
PHP 8.x Application Boundary
Router / Session / REST / Services
          │
          ├──────── Public Story repositories
          │
          ├──────── Content Hub repositories
          │
          ├──────── Redis-ready runtime
          │
          └──────── HTTP → 127.0.0.1:8788
                          │
                          ▼
                 Python FastAPI Engine
                          │
            ┌─────────────┼─────────────┐
            ▼             ▼             ▼
        Providers      Normalize    Intelligence
            │                           │
    Manual/WP/IG/YT/X          category/location/
                              language/duplicate/
                              related/summary
```

## Frozen identity rule

Every canonical persistent entity uses UUIDv7. Provider IDs are stored separately as `external_id`.

## Frozen persistence rule

The application remains No-DB first. Canonical records are atomic JSON documents and append-only JSONL events behind repository interfaces. No controller or UI code may read persistence files directly.

## Frozen source rule

Provider acquisition and public presentation are independent. All providers normalize into the same `Content` contract before intelligence or publication.

## Frozen intelligence rule

Deterministic rules and heuristics are available without an external model. Any external AI is an optional private adapter and must preserve the original analyzer result, confidence, provenance, and later editorial override.

## M4/M5 canonical collections

```text
sources
contents
media
categories
locations
rules
analysis-results
story-clusters
stories
```

## Security boundary

Provider tokens are referenced by environment variable names from source configuration. Token values are resolved only for the private engine request and are not returned by the public product API.

M6 adds newsroom RBAC/editorial authorization; M7 adds encrypted credential management and production security controls.

## M6 newsroom plane

M6 adds an operational plane above the existing content contracts:

```text
Sources → Normalize → Intelligence → Content
                                  │
                                  ▼
                           Editorial Inbox
                                  │
                     approve/review/reject
                                  │
                                  ▼
                          Canonical Story
                                  │
                    ┌─────────────┼─────────────┐
                    ▼             ▼             ▼
                  Feed          Search      Notifications

Scheduler → File Job Queue → Worker → Source Sync
```

The newsroom layer does not bypass provider normalization or create a second publication model. Approval routes through the same M5 intelligence/cluster pipeline and publishes the same Story contract used by M2.

## M7 security/operations boundary

```text
Browser
  ↓
SecurityHeaders + SecurityKernel
  ├─ body-size guard
  ├─ rate limiting
  ├─ CSRF + same-origin checks
  └─ hardened session cookie
  ↓
PHP router / services
  ├─ CredentialVault (AES-256-GCM)
  ├─ QuotaService
  ├─ ErrorCenter
  └─ repositories
        ↓
   No-DB JSON / JSONL

PHP → FastAPI provider calls
          ↓
      URL/SSRF Guard
          ↓
       Providers
```

The source credential vault and filesystem content store are intentionally separate. A content export/backup does not implicitly export source API secrets.
