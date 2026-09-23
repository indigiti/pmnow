#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"; cd "$ROOT"
PHP_PORT="${M7_PHP_PORT:-18080}"; ENGINE_PORT="${M7_ENGINE_PORT:-18788}"
APP="http://127.0.0.1:${PHP_PORT}"; ENGINE="http://127.0.0.1:${ENGINE_PORT}"
PYTHONPATH=. python3 -m uvicorn engine.main:app --host 127.0.0.1 --port "$ENGINE_PORT" >storage/logs/m7-http-engine.log 2>&1 & EPID=$!
APP_URL="$APP" PYTHON_ENGINE_URL="$ENGINE" APP_KEY="${APP_KEY:-dev-only-change-this-key-32chars}" ADMIN_DEV_BYPASS=false php -S "127.0.0.1:${PHP_PORT}" -t public public/index.php >storage/logs/m7-http-php.log 2>&1 & PPID2=$!
cleanup(){ kill "$EPID" "$PPID2" 2>/dev/null || true; }
trap cleanup EXIT
for _ in $(seq 1 50); do if curl -fsS "$APP/api/v1/health" >/dev/null 2>&1; then break; fi; sleep .15; done
headers="$(curl -fsS -D - -o /dev/null "$APP/")"
grep -qi '^Content-Security-Policy:' <<<"$headers" || { echo 'FAIL: CSP header'; exit 1; }
grep -qi '^X-Content-Type-Options: nosniff' <<<"$headers" || { echo 'FAIL: nosniff header'; exit 1; }
COOKIE="$(mktemp)"; PAGE="$(mktemp)"; trap 'rm -f "$COOKIE" "$PAGE"; cleanup' EXIT
curl -fsS -c "$COOKIE" "$APP/admin/login" >"$PAGE"
TOKEN="$(python3 - "$PAGE" <<'PY'
import re,sys
m=re.search(r'name="csrf-token" content="([^"]+)"',open(sys.argv[1]).read()); print(m.group(1) if m else '')
PY
)"
[ -n "$TOKEN" ] || { echo 'FAIL: CSRF meta token'; exit 1; }
NO="$(curl -sS -o /dev/null -w '%{http_code}' -b "$COOKIE" -H 'Content-Type: application/json' -d '{"email":"admin@punemirror.local","password":"ChangeMe123!"}' "$APP/api/admin/session")"
[ "$NO" = "419" ] || { echo "FAIL: missing CSRF expected 419 got $NO"; exit 1; }
YES="$(curl -sS -o /dev/null -w '%{http_code}' -b "$COOKIE" -c "$COOKIE" -H 'Content-Type: application/json' -H "X-CSRF-Token: $TOKEN" -d '{"email":"admin@punemirror.local","password":"ChangeMe123!"}' "$APP/api/admin/session")"
[ "$YES" = "200" ] || { echo "FAIL: valid CSRF/login expected 200 got $YES"; exit 1; }
echo 'M7 HTTP security smoke: PASS'
