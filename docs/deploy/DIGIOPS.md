# DigiOps deployment: `pmnow`

DigiOps application configuration:

- Repository: `indigiti/pmnow`
- Branch: `main`
- Browser route: `/pmnow/`
- Public folder: `public_html/pmnow/`
- Private folder: `private_html/pmnow/`
- GitHub Actions artifact: `digiops-release`

The artifact is intentionally split:

```text
public/
  index.php
  .htaccess
  health.php
  ready.php
  assets/
  build/        # when Vite has built hashed assets
  media/
  RELEASE.json

private/
  app/
  bootstrap/
  config/
  contracts/
  engine/
  resources/
  tools/
  storage/      # empty markers only; runtime survives overlays
  .env.production.example
  RELEASE.json
```

## One-time staging demo configuration (DigiOps UI)

The PMNow frontend will **automatically populate synthetic published stories on its first staging request** when the private app configuration specifies the exact environment below. No newsroom approval, Bash command, or manual Load action is needed. This does not run on production.

In DigiOps, deploy the certified `main` branch artifact named `digiops-release` and configure the private application environment (through DigiOps application settings or its Cloudways deployment environment management):

```ini
APP_ENV=staging
APP_URL=https://stage.digiti.in/pmnow
APP_BASE_PATH=/pmnow
PMNOW_DEMO_DATA_ALLOWED=true
ADMIN_DEV_BYPASS=false
SESSION_SECURE_COOKIE=true
```

These settings must be supplied to the **private PHP application runtime**, not placed in any public file or committed into the repository. The private `.env` file is now read *before* production defaults, so a staging configuration is effective on Cloudways as well as ordinary FPM environments. If no explicit opt-in exists, the safe default remains production and auto-seeding is disabled.

The first website request creates additive, clearly labelled synthetic data using a one-time persistent marker in `storage/cache`. That marker survives normal DigiOps overlays and prevents re-creation after an admin removes demo records. Admin → Demo Data can still load or remove them manually. Do not use the demo environment configuration on a live production site.

## First deployment

After DigiOps deploys the certified artifact, the public front controller boots with safe production defaults. If `APP_KEY` is not configured it creates a private 256-bit key at `private_html/pmnow/storage/secrets/app.key` (mode 0600). When creating `private_html/pmnow/.env`, copy that generated value into `APP_KEY` so encrypted source credentials continue to use the same key.

To load the demo newsroom/feed on a first install, supply an explicit strong bootstrap password:

```bash
APP_ENV=production \
ADMIN_BOOTSTRAP_EMAIL='admin@example.com' \
ADMIN_BOOTSTRAP_PASSWORD='replace-with-a-strong-password' \
php private_html/pmnow/tools/seed.php
```

On production, `seed.php` refuses to overwrite existing application data unless `--force` is deliberately supplied.

Health probes:

- `/pmnow/health.php` — deployment/runtime presence
- `/pmnow/ready.php` — bootstrap + writable private storage
- `/pmnow/api/v1/health` — application API health

Python/FastAPI is private and optional for the first UI deployment. Configure/supervise it separately before enabling provider sync or intelligence jobs.
