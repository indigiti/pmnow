#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
[ -f .env ] || cp .env.example .env
set -a
# shellcheck disable=SC1091
source .env
set +a

ENGINE_PID=""
cleanup() {
  if [ -n "${ENGINE_PID}" ] && kill -0 "${ENGINE_PID}" 2>/dev/null; then
    kill "${ENGINE_PID}" 2>/dev/null || true
  fi
}
trap cleanup EXIT INT TERM

if [ "${ENGINE_AUTOSTART:-true}" = "true" ]; then
  python3 -m uvicorn engine.main:app --host 127.0.0.1 --port "${ENGINE_PORT:-8788}" > storage/logs/engine.log 2>&1 &
  ENGINE_PID=$!
  python3 - <<'PY'
import os, time, urllib.request
url=os.getenv('PYTHON_ENGINE_URL','http://127.0.0.1:8788').rstrip('/')+'/health'
for _ in range(40):
    try:
        with urllib.request.urlopen(url, timeout=.5) as r:
            if r.status == 200:
                print('Content engine: ready')
                break
    except Exception:
        time.sleep(.15)
else:
    raise SystemExit('Content engine failed to start; see storage/logs/engine.log')
PY
fi

if ! compgen -G 'storage/data/stories/*.json' > /dev/null; then
  php tools/seed.php
fi
php tools/doctor.php
php -S 127.0.0.1:${PORT:-8080} -t public tools/dev-router.php
