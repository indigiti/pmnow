# Implementation Status — M1 through M7

## M1 — Foundation — COMPLETE
- [x] PHP custom router/front controller
- [x] UUIDv7 identity
- [x] shared JSON contracts
- [x] atomic JSON/JSONL persistence
- [x] repository boundary / DB-ready identities

## M2 — Mobile Product Alpha — COMPLETE
- [x] Home, Story, Explore, Gallery, Developing, Live, Watch/Reels

## M3 — User Product Beta — COMPLETE
- [x] reader session, bookmarks, follows, preferences, Redis-ready runtime

## M4 — Content Hub Alpha — COMPLETE
- [x] private FastAPI engine and provider registry
- [x] Manual, WordPress, Instagram, YouTube and X adapters
- [x] source test/sync and normalized content

## M5 — Intelligence Beta — COMPLETE
- [x] language/category/location/entity/summary analysis
- [x] versioned results
- [x] duplicate/related detection
- [x] Story Clusters

## M6 — Newsroom Release — COMPLETE
- [x] newsroom/RBAC, Editorial Inbox, bulk workflow/audit
- [x] source administration/health
- [x] jobs/scheduler/worker with retry
- [x] filesystem search
- [x] follow notifications

## M7 — Production Hardening — COMPLETE
- [x] central CSP/security headers
- [x] CSRF protection for state-changing APIs
- [x] same-origin mutation guard
- [x] request-size limits
- [x] admin-login and mutation rate limits
- [x] strict/hardened session handling
- [x] PHP + Python SSRF guards with redirect re-validation
- [x] encrypted AES-256-GCM per-source credential vault
- [x] masked credential-management newsroom UI
- [x] soft source quota counters/statuses
- [x] centralized Error Center with secret redaction
- [x] System Health newsroom screen
- [x] safer media URL/metadata validation
- [x] Vite manifest asset support with zero-install fallback
- [x] Nginx/systemd deployment templates
- [x] security/health/key-generation tools
- [x] M7 PHP + Python smoke tests

## Deliberately beyond M7
- database repository driver + File→DB migration
- vendor-authoritative quota APIs (M7 uses configurable soft limits)
- full image/video download/transcode/CDN pipeline
- Web Push transport
- multi-node distributed queue locking
- external observability/SIEM integration

## M8 — Experience Foundation — COMPLETE
- [x] semantic design tokens and modular CSS
- [x] responsive mobile/desktop public shell
- [x] SVG icon system and accessibility pass
- [x] public/newsroom visual refresh

## M8.1 — Growth Engineering Foundation — COMPLETE
- [x] channel-filter browser regression fix
- [x] locked/atomic derived-index writes
- [x] cursor-based feed API pagination
- [x] JSONL analytics event foundation
- [x] provider external-ID lookup index
- [x] Playwright browser smoke coverage
- [x] committed npm lockfile with npm-ci certification
- [ ] visual screenshot baselines


## M9 — SEO / News Discovery Foundation — IN PROGRESS
- [x] SEO-friendly canonical story URLs
- [x] title/description/canonical/OpenGraph/Twitter metadata
- [x] NewsArticle JSON-LD
- [x] sitemap.xml
- [x] news-sitemap.xml
- [x] RSS feed
- [x] category and neighbourhood landing pages
- [x] max-image-preview:large
- [ ] production Search Console / Google News validation after deployment
- [ ] visual/social-card optimization from real production stories

## M10 — My Pune Personalization — COMPLETE
- [x] editable neighbourhood preferences
- [x] editable topic preferences
- [x] deterministic For You ranking
- [x] neighbourhood/topic ranking reasons
- [x] personalized Home ordering
- [x] cursor-compatible personalized feed service
- [x] saved-story retrieval independent of latest-feed window
- [x] dedicated Near You surface
- [x] newsroom analytics for preference/retention cohorts


## M11 — Distribution Engine — IN PROGRESS
- [x] reader notification preference model
- [x] breaking-alert targeting
- [x] neighbourhood alert targeting from My Pune areas
- [x] topic alert targeting from My Pune topics
- [x] idempotent in-app distribution
- [x] Morning / Evening / Weekend digest primitives
- [x] scheduler + worker digest job contracts
- [x] newsroom distribution controls
- [x] WhatsApp-ready and Telegram-ready JSON channel feeds
- [x] Web Push subscription registry + service worker contract
- [x] browser and PHP M11 regression coverage
- [ ] Web Push encrypted sending transport with private VAPID credentials
- [ ] WhatsApp/Telegram provider delivery adapters and credentials
- [ ] production delivery-rate / unsubscribe telemetry
