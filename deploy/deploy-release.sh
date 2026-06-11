#!/usr/bin/env bash
# =============================================================================
#  deploy-release.sh — run as root on the AIR-GAPPED VM1.
#  Deploys a pre-built artifact from build-release.sh. No internet needed:
#  vendor/ and public/build are inside the artifact; migrations run locally.
#
#  Layout (created automatically on first run from the existing flat install):
#      /var/www/aamar-malda/releases/<timestamp>/   code (one dir per deploy)
#      /var/www/aamar-malda/shared/.env             persistent config (secret)
#      /var/www/aamar-malda/shared/storage/         persistent uploads/logs
#      /var/www/aamar-malda/current -> releases/<x> atomic switch (nginx root)
#
#  Usage:   deploy-release.sh <artifact.tar.gz>      deploy a new release
#           deploy-release.sh --rollback             switch back one release
# =============================================================================
set -euo pipefail

BASE="/var/www/aamar-malda"
KEEP=5                      # releases to keep for rollback
PHP_USER="nginx"

ts(){ date '+%F %T'; }
log(){  printf '%s [deploy] %s\n' "$(ts)" "$*"; }
warn(){ printf '%s [deploy:WARN] %s\n' "$(ts)" "$*"; }
die(){  printf '%s [deploy:ERROR] %s\n' "$(ts)" "$*" >&2; exit 1; }
[[ $EUID -eq 0 ]] || die "run as root"

flip(){  # atomically point $BASE/current at $1
  rm -f "$BASE/.current.tmp"
  ln -s "$1" "$BASE/.current.tmp"
  mv -T "$BASE/.current.tmp" "$BASE/current"
}

restart_runtime(){
  systemctl restart php-fpm                       # required: OPcache validate_timestamps=0
  systemctl restart aamar-queue 2>/dev/null || true
  log "php-fpm + queue worker restarted"
}

load_version(){  # export baked APP_VERSION/APP_COMMIT so they freeze into config:cache
  local f="$1/.deploy-version"
  if [[ -f "$f" ]]; then
    set -a; # shellcheck disable=SC1090
    source "$f"; set +a
    log "footer version: v${APP_VERSION:-?} · ${APP_COMMIT:0:7}"
  else
    warn "no .deploy-version in release — footer falls back to config default (v0.1.0 · dev)"
  fi
}

# ---- rollback ---------------------------------------------------------------
if [[ "${1:-}" == "--rollback" ]]; then
  cur="$(readlink -f "$BASE/current" 2>/dev/null)" || die "no current release"
  mapfile -t RELS < <(find "$BASE/releases" -mindepth 1 -maxdepth 1 -type d | sort)
  prev=""
  for r in "${RELS[@]}"; do
    [[ "$r" == "$cur" ]] && break
    prev="$r"
  done
  [[ -n "$prev" ]] || die "no previous release to roll back to"
  flip "$prev"
  restart_runtime
  log "rolled back: $(basename "$cur") -> $(basename "$prev")"
  log "NOTE: database migrations are NOT rolled back automatically."
  exit 0
fi

ART="${1:?usage: deploy-release.sh <artifact.tar.gz> | --rollback}"
[[ -f "$ART" ]] || die "artifact not found: $ART"

# ---- integrity check ----------------------------------------------------------
if [[ -f "$ART.sha256" ]]; then
  ( cd "$(dirname "$ART")" && sha256sum --check --quiet "$(basename "$ART").sha256" ) \
    || die "sha256 mismatch — artifact corrupted in transfer"
  log "sha256 verified"
else
  warn "no $ART.sha256 alongside the artifact — skipping integrity check"
fi

# ---- one-time: migrate flat layout -> releases/shared/current ------------------
if [[ ! -L "$BASE/current" ]]; then
  if [[ -f "$BASE/artisan" ]]; then
    warn "first run: converting flat layout to releases/ (brief downtime, seconds)"
    TMP="$(mktemp -d /var/www/.aamar-migrate.XXXXXX)"
    mv "$BASE" "$TMP/app"
    install -d -m 0755 "$BASE/releases" "$BASE/shared"
    mv "$TMP/app/.env"     "$BASE/shared/.env"
    mv "$TMP/app/storage"  "$BASE/shared/storage"
    INIT="$BASE/releases/00000000-initial"
    mv "$TMP/app" "$INIT"; rmdir "$TMP"
    ln -s "$BASE/shared/.env"    "$INIT/.env"
    ln -s "$BASE/shared/storage" "$INIT/storage"
    flip "$INIT"

    # nginx now serves current/public
    sed -i "s|root $BASE/public;|root $BASE/current/public;|" /etc/nginx/conf.d/aamar-malda.conf
    nginx -t && systemctl reload nginx

    # queue worker follows current/
    if [[ -f /etc/systemd/system/aamar-queue.service ]]; then
      sed -i -e "s|$BASE/artisan|$BASE/current/artisan|" \
             -e "s|^WorkingDirectory=$BASE$|WorkingDirectory=$BASE/current|" \
             /etc/systemd/system/aamar-queue.service
      systemctl daemon-reload
    fi

    # backup fetch script reads .env from its new home
    sed -i 's|APP_ENV="$APP_DIR/.env"|APP_ENV="$APP_DIR/shared/.env"|' \
      /usr/local/sbin/vm1-fetch-backup.sh 2>/dev/null || true

    # scheduler cron + logrotate follow the new layout (if installed pre-migration)
    sed -i -e "s|cd $BASE &&|cd $BASE/current \&\&|" \
           -e "s|$BASE/storage/logs/schedule.log|$BASE/shared/storage/logs/schedule.log|" \
      /etc/cron.d/aamar-schedule 2>/dev/null || true
    sed -i "s|$BASE/storage/logs/schedule.log|$BASE/shared/storage/logs/schedule.log|" \
      /etc/logrotate.d/aamar-schedule 2>/dev/null || true

    # SELinux: writable contexts for the new persistent paths
    if command -v semanage >/dev/null; then
      semanage fcontext -a -t httpd_sys_rw_content_t "$BASE/shared/storage(/.*)?" 2>/dev/null || true
      semanage fcontext -a -t httpd_sys_rw_content_t "$BASE/releases/[^/]+/bootstrap/cache(/.*)?" 2>/dev/null || true
    fi
    command -v restorecon >/dev/null && restorecon -RF "$BASE" >/dev/null 2>&1 || true

    # cached config holds absolute paths — regenerate for the moved code
    ( cd "$INIT" && php artisan config:clear -q && php artisan config:cache -q \
        && php artisan route:cache -q && php artisan view:cache -q \
        && php artisan event:cache -q ) \
      || warn "cache regeneration in the initial release failed — check manually"
    chown -R "$PHP_USER:$PHP_USER" "$INIT" "$BASE/shared"
    restart_runtime
    log "migration done — existing app preserved as releases/00000000-initial"
  else
    install -d -m 0755 "$BASE/releases" "$BASE/shared"
  fi
fi
[[ -f "$BASE/shared/.env" ]] || die "missing $BASE/shared/.env — production config must exist"

# ---- unpack + validate the new release -----------------------------------------
STAMP="$(date +%Y%m%d-%H%M%S)"
REL="$BASE/releases/$STAMP"
INCOMING="$BASE/releases/.incoming-$STAMP"
trap 'rm -rf "$INCOMING"' EXIT
install -d "$INCOMING"
log "unpacking $(basename "$ART") ..."
tar xzf "$ART" -C "$INCOMING"
[[ -f "$INCOMING/artisan" && -d "$INCOMING/vendor" && -d "$INCOMING/public/build" ]] \
  || die "artifact incomplete — need artisan, vendor/, public/build/ (use build-release.sh)"
mv "$INCOMING" "$REL"
trap - EXIT

# shared state: .env + storage live outside the release
rm -rf "$REL/storage"
ln -s "$BASE/shared/storage" "$REL/storage"
rm -f "$REL/.env"
ln -s "$BASE/shared/.env"    "$REL/.env"
install -d "$BASE/shared/storage/app/public" \
           "$BASE/shared/storage/framework/cache/data" \
           "$BASE/shared/storage/framework/sessions" \
           "$BASE/shared/storage/framework/views" \
           "$BASE/shared/storage/logs"

# ---- migrations + caches (offline; uses shared .env) ----------------------------
cd "$REL"
log "running database migrations ..."
php artisan migrate --force
load_version "$REL"            # APP_VERSION/APP_COMMIT -> frozen into this release's config cache
log "caching config/routes/views/events ..."
php artisan config:cache -q && php artisan route:cache -q \
  && php artisan view:cache -q && php artisan event:cache -q
php artisan storage:link --force >/dev/null 2>&1 || true

chown -R "$PHP_USER:$PHP_USER" "$REL" "$BASE/shared"
command -v restorecon >/dev/null && restorecon -RF "$REL" "$BASE/shared" >/dev/null 2>&1 || true

# ---- atomic switch ---------------------------------------------------------------
flip "$REL"
restart_runtime
[[ -f "$REL/RELEASE_INFO" ]] && log "live: $(tr '\n' ' ' < "$REL/RELEASE_INFO")"
log "deployed release $STAMP"

# ---- prune (keep $KEEP, never the live one) ---------------------------------------
cur="$(readlink -f "$BASE/current")"
mapfile -t ALL < <(find "$BASE/releases" -mindepth 1 -maxdepth 1 -type d | sort -r)
n=0
for r in "${ALL[@]}"; do
  n=$((n+1))
  (( n <= KEEP )) && continue
  [[ "$r" == "$cur" ]] && continue
  rm -rf "$r"; log "pruned old release $(basename "$r")"
done
log "done — verify the footer tag reads v${APP_VERSION:-?}, then:  curl -sI http://localhost/ | head -1   and   systemctl status aamar-queue"
