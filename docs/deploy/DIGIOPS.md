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
