#!/usr/bin/env bash
# =============================================================================
#  build-release.sh — run on an INTERNET-CONNECTED build machine (dev box/WSL).
#  Produces a fully self-contained release artifact for the air-gapped VM1:
#  application code + vendor/ (composer --no-dev) + compiled public/build (vite).
#  VM1 never needs git/composer/npm.
#
#  Usage:  ./build-release.sh [git-ref]          (default: master)
#  Env:    REPO_URL, OUT_DIR overridable.
#  NOTE: build with a PHP compatible with production (>=8.3; prod runs 8.4).
# =============================================================================
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/abusalam/mango-orchard.git}"
REF="${1:-master}"
OUT_DIR="${OUT_DIR:-$PWD/dist}"

log(){ printf '[build] %s\n' "$*"; }
die(){ printf '[build:ERROR] %s\n' "$*" >&2; exit 1; }
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

log "composer install --no-dev (exact versions from composer.lock) ..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

log "building front-end assets (npm ci + vite build) ..."
npm ci
npm run build
[[ -d public/build ]] || die "public/build missing — vite build failed"

printf 'ref=%s\ncommit=%s\nbuilt=%s\nbuild_php=%s\n' \
  "$REF" "$GITSHA" "$(date -Is)" "$PHPV" > RELEASE_INFO

log "packing artifact (no .git / node_modules / tests / .env) ..."
mkdir -p "$OUT_DIR"
NAME="aamar-app-${REF//\//-}-$TS-$GITSHA"
tar czf "$OUT_DIR/$NAME.tar.gz" \
  --exclude='./.git' --exclude='./node_modules' --exclude='./tests' \
  --exclude='./.github' --exclude='./.env' \
  -C "$WORK/app" .
( cd "$OUT_DIR" && sha256sum "$NAME.tar.gz" > "$NAME.tar.gz.sha256" )

log "DONE -> $OUT_DIR/$NAME.tar.gz  ($(du -h "$OUT_DIR/$NAME.tar.gz" | cut -f1))"
log "transfer BOTH files (tar.gz + .sha256) to VM1, then run:"
log "    deploy-release.sh $NAME.tar.gz"
