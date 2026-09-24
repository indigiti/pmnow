# PMNow — Pune Mirror mobile news experience

PMNow is the mobile-first Pune Mirror visual news application and multi-source content intelligence hub.

## Runtime stack

- PHP 8.x application/API/router
- Tailwind CSS + GSAP + Vite frontend
- Python/FastAPI provider and intelligence engine
- Redis optional runtime cache/queue/pub-sub
- No-DB filesystem persistence by default; database adapter boundary retained
- UUIDv7 entity IDs

## DigiOps deployment

This repository is prepared for the DigiOps application `pmnow`:

- Browser route: `/pmnow/`
- Public target: `public_html/pmnow/`
- Private target: `private_html/pmnow/`
- Git branch: `main`
- Certified artifact: `digiops-release`

The GitHub release workflow builds an artifact with exactly:

```text
public/   -> public_html/pmnow/
private/  -> private_html/pmnow/
```

Only the public front controller, compiled/static assets, demo media, `.htaccess`, `health.php`, and `ready.php` are web-accessible. PHP application code, JSON persistence, provider credentials, logs, queues, Python services, tests/tools, and runtime metadata remain private.

### First deployment

1. Push/merge to `main`.
2. Wait for **PMNow Security and Syntax** to pass.
3. Wait for **PMNow Certified Release Artifact** to publish `digiops-release`.
4. In DigiOps select **Check update**, then **Deploy**.
5. Verify `/pmnow/health.php` and `/pmnow/ready.php`.
6. Open `/pmnow/health.php` once; if no `APP_KEY` exists, PMNow creates `private_html/pmnow/storage/secrets/app.key` with a random 256-bit key.
7. Create `private_html/pmnow/.env` from `.env.production.example`; set `APP_URL=https://<your-host>/pmnow` and copy the generated private key into `APP_KEY`.
8. If demo content is desired, seed once with an explicit strong admin password (see `docs/deploy/DIGIOPS.md`).

The public front controller automatically detects the DigiOps split layout. If `.env` is not present it defaults to production-safe behavior (`APP_DEBUG=false`, admin bypass off, CSRF on, secure cookies on) and keeps the generated key outside the web root.

## Local development

```bash
cp .env.example .env
python3 -m pip install -r engine/requirements.txt
ADMIN_BOOTSTRAP_EMAIL='admin@example.com' ADMIN_BOOTSTRAP_PASSWORD='replace-with-a-strong-password' php tools/seed.php
./run.sh
```

Open `http://127.0.0.1:8080`.

## Release package

```bash
bash ops/package-release.sh
```

The staged deployable tree is written to `dist/pmnow/`.

## M8.1 growth foundation

M8.1 adds browser-level certification, cursor feed pagination, a privacy-conscious JSONL analytics ledger, concurrent-safe derived indexes, and indexed provider external-ID lookup. Playwright browser smoke covers the M8 channel interaction that syntax-only CI could not detect.


## Phase 1 growth roadmap

PMNow now carries the M8.1 growth-engineering foundation and the first M9/M10 implementation. Public stories use SEO-friendly canonical URLs, Google News-compatible discovery feeds, and NewsArticle metadata. My Pune stores reader-selected neighbourhoods/topics in the existing No-DB user repository and uses them in a deterministic For You ranking. PostgreSQL remains deliberately deferred until M18.


## M11 distribution foundation

M11 adds reader-controlled breaking, neighbourhood and topic alerts; Morning/Evening/Weekend briefing jobs; idempotent in-app delivery; newsroom distribution controls; channel-ready JSON feeds; and a Web Push subscription/service-worker contract. External Web Push encryption and messaging-provider transports remain credentialed adapters rather than hidden dependencies, so PMNow continues to run fully in No-DB/file mode without third-party services.


## M12 Pune Utility foundation

PMNow now has a separate No-DB utility domain for traffic, transit, weather, air quality, civic notices, outages, emergencies and events. Reader-facing utility cards carry explicit source and verification metadata, support area-aware ranking from My Pune preferences, and can be followed for alerts. Newsroom editors can create utility entities, publish advisories and resolve them from the Utility Desk. Demo records are limited to local/CI seeding and are source-labelled; production does not infer live utility conditions when verified data is unavailable. Official provider adapters and freshness automation remain the next M12 slice.
