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
