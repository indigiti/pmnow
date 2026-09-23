#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
SOURCE_SHA="${SOURCE_SHA:-$(git rev-parse HEAD 2>/dev/null || printf 'local')}"
OUTPUT_DIR="${OUTPUT_DIR:-$ROOT/dist}"
VERSION="$(tr -d '[:space:]' < VERSION)"
STAGE="$OUTPUT_DIR/pmnow"
PUBLIC="$STAGE/public"
PRIVATE="$STAGE/private"
rm -rf "$STAGE"
mkdir -p "$PUBLIC" "$PRIVATE"

copy_tree(){
  local src="$1" dst="$2"
  mkdir -p "$dst"
  cp -a "$src"/. "$dst"/
}

# Public web payload. Vite build/ is used when present; assets/ is a checked-in fallback.
copy_tree public "$PUBLIC"

# Private application payload. Writable runtime state is created as empty directories below.
for dir in app bootstrap config contracts engine resources tools; do
  copy_tree "$dir" "$PRIVATE/$dir"
done
cp composer.json package.json vite.config.js VERSION .env.production.example "$PRIVATE"/
[[ -f package-lock.json ]] && cp package-lock.json "$PRIVATE"/

# Runtime storage must survive releases; deploy only directory markers, never current data/secrets/logs.
for dir in data indexes events cache logs secrets raw imports analysis clusters; do
  mkdir -p "$PRIVATE/storage/$dir"
  : > "$PRIVATE/storage/$dir/.gitkeep"
done

# Never ship tests, local env files, Python caches, or runtime state.
find "$STAGE" -type f \( -name '.env' -o -name '*.pyc' -o -name '*.pyo' \) -delete
find "$STAGE" -type d -name '__pycache__' -prune -exec rm -rf {} +

for required in \
  "$PUBLIC/index.php" "$PUBLIC/.htaccess" "$PUBLIC/health.php" "$PUBLIC/ready.php" \
  "$PUBLIC/assets/app.css" "$PUBLIC/assets/app.js" \
  "$PRIVATE/bootstrap/app.php" "$PRIVATE/config/app.php" "$PRIVATE/app/Core/Router.php" \
  "$PRIVATE/resources/views/layout.php" "$PRIVATE/engine/main.py" "$PRIVATE/tools/seed.php"; do
  [[ -f "$required" ]] || { echo "Required release file missing: ${required#$STAGE/}" >&2; exit 1; }
done

if find "$PRIVATE/storage" -type f ! -name '.gitkeep' -print -quit | grep -q .; then
  echo "Runtime storage leaked into release payload" >&2; exit 1
fi
if find "$STAGE" -type f -name '.env' -print -quit | grep -q .; then
  echo "Environment file leaked into release payload" >&2; exit 1
fi

cat > "$PUBLIC/RELEASE.json" <<JSON
{
  "schema_version": 1,
  "app": "PMNow",
  "version": "$VERSION",
  "source_sha": "$SOURCE_SHA",
  "artifact_policy": "digiops-split-public-private"
}
JSON
cp "$PUBLIC/RELEASE.json" "$PRIVATE/RELEASE.json"

(
  cd "$STAGE"
  find public private -type f ! -name SHA256SUMS -print0 | LC_ALL=C sort -z | xargs -0 sha256sum > SHA256SUMS
)

echo "release_version=$VERSION"
echo "release_source_sha=$SOURCE_SHA"
echo "release_stage=$STAGE"
echo "release_public=$PUBLIC"
echo "release_private=$PRIVATE"
