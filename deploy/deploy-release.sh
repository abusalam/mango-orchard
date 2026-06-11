#!/usr/bin/env bash
# =============================================================================
#  build-release.sh — run on an INTERNET-CONNECTED build machine (dev box/WSL).
#  Produces a fully self-contained release artifact for the air-gapped VM1:
#  application code + vendor/ (composer --no-dev) + compiled public/build (vite).
#  VM1 never needs git/composer/npm.
#
#  Versioning (per docs/DEPLOY.md): the app footer shows "v{APP_VERSION} ·
#  {short APP_COMMIT}" via App\Support\Version. Because the artifact strips .git
#  (the doc's own advice), this script BAKES both values into the artifact
#  (.deploy-version) so deploy-release.sh can freeze them into the release's
#  config cache. No .git on the server, correct footer tag.
#
#  Usage:  ./build-release.sh [git-ref]          (default: master; use a tag, e.g. v1.2.3)
#  Env:    REPO_URL, OUT_DIR, APP_VERSION (override the derived semver).
#  NOTE: build with a PHP compatible with production (>=8.3; prod runs 8.4).
# =============================================================================
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/abusalam/mango-orchard.git}"
REF="${1:-master}"
OUT_DIR="${OUT_DIR:-$PWD/dist}"

log(){  printf '[build] %s\n' "$*"; }
warn(){ printf '[build:WARN] %s\n' "$*"; }
die(){  printf '[build:ERROR] %s\n' "$*" >&2; exit 1; }
command -v git >/dev/null      || die "git missing"
command -v composer >/dev/null || die "composer missing"
command -v node >/dev/null     || die "node missing"
command -v npm >/dev/null      || die "npm missing"
PHPV="$(php -r 'echo PHP_VERSION;')"
log "building with PHP $PHPV (production is 8.4 — keep compatible, see composer.lock)"

TS="$(date +%Y%m%d-%H%M%S)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

log "cloning $REPO_URL @ $REF ..."
git clone --depth 1 --branch "$REF" "$REPO_URL" "$WORK/app"
cd "$WORK/app"
GITSHA="$(git rev-parse --short HEAD)"
FULLSHA="$(git rev-parse HEAD)"

# ---- derive the footer semver (App\Support\Version / config/app.php) ----------
# Priority: explicit APP_VERSION > semver-looking ref/tag > nearest git tag >
# the app's own default (0.1.0). Stamping it makes support tickets quote a real
# number instead of "v0.1.0 · dev".
if [[ -n "${APP_VERSION:-}" ]]; then
  VERSION="$APP_VERSION"
elif [[ "$REF" =~ ^v?([0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z.]+)?)$ ]]; then
  VERSION="${BASH_REMATCH[1]}"
else
  DESC="$(git describe --tags --always 2>/dev/null || true)"
  if [[ "$DESC" =~ ^v?([0-9]+\.[0-9]+\.[0-9]+) ]]; then
    VERSION="${BASH_REMATCH[1]}"
  else
    VERSION="0.1.0"
    warn "no semver tag on '$REF' — defaulting APP_VERSION=0.1.0; tag releases (git tag -a vX.Y.Z) for a meaningful footer"
  fi
fi
log "release version: v$VERSION · $GITSHA  (commit $FULLSHA)"

log "composer install --no-dev (exact versions from composer.lock) ..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

log "building front-end assets (npm ci + vite build) ..."
npm ci
npm run build
[[ -d public/build ]] || die "public/build missing — vite build failed"

# Baked version, sourced by deploy-release.sh and exported before config:cache.
printf "APP_VERSION='%s'\nAPP_COMMIT='%s'\n" "$VERSION" "$FULLSHA" > .deploy-version
# Human-readable manifest.
printf 'ref=%s\nversion=%s\ncommit=%s\ncommit_full=%s\nbuilt=%s\nbuild_php=%s\n' \
  "$REF" "$VERSION" "$GITSHA" "$FULLSHA" "$(date -Is)" "$PHPV" > RELEASE_INFO

log "packing artifact (no .git / node_modules / tests / .env) ..."
mkdir -p "$OUT_DIR"
NAME="aamar-app-v${VERSION}-${TS}-${GITSHA}"
tar czf "$OUT_DIR/$NAME.tar.gz" \
  --exclude='./.git' --exclude='./node_modules' --exclude='./tests' \
  --exclude='./.github' --exclude='./.env' \
  -C "$WORK/app" .
( cd "$OUT_DIR" && sha256sum "$NAME.tar.gz" > "$NAME.tar.gz.sha256" )

log "DONE -> $OUT_DIR/$NAME.tar.gz  ($(du -h "$OUT_DIR/$NAME.tar.gz" | cut -f1))"
log "footer tag will read:  v$VERSION · $GITSHA"
log "transfer BOTH files (tar.gz + .sha256) to VM1, then run:"
log "    deploy-release.sh $NAME.tar.gz"
