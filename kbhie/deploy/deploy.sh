#!/usr/bin/env bash
#==============================================================================
#  Khoobie zero-downtime deployer
#  -----------------------------------------------------------------------------
#  Run on the production server. Pulls latest main, runs composer + DB
#  migrations, atomically swaps the symlinked release. Keeps last 3 releases
#  for instant rollback (see rollback.sh).
#
#  Layout on the server:
#    /var/www/khoobie/
#      ├── current        →  releases/2026-05-28-141533/   (symlink)
#      ├── releases/
#      │     2026-05-28-141533/      ← this deploy
#      │     2026-05-27-201212/      ← previous (kept for rollback)
#      │     2026-05-26-093045/      ← older (kept for rollback)
#      └── shared/
#            .env                    ← never touched
#            writable/uploads/       ← user uploads — symlinked into each release
#            writable/session/       ← sessions — symlinked into each release
#            writable/logs/          ← logs — symlinked into each release
#
#  Apache/Nginx DocumentRoot points at /var/www/khoobie/current/public
#
#  Usage:
#    ./deploy/deploy.sh                    # deploy main
#    ./deploy/deploy.sh feature/branch     # deploy a specific branch
#==============================================================================

set -euo pipefail

# ---- CONFIG (edit these once per server) ------------------------------------
APP_ROOT="${APP_ROOT:-/var/www/khoobie}"
REPO_URL="${REPO_URL:-git@github.com:khoobie/khoobie-ecom.git}"
BRANCH="${1:-main}"
KEEP_RELEASES=3
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
WEB_USER="${WEB_USER:-www-data}"
# ---- /CONFIG ----------------------------------------------------------------

STAMP="$(date +%Y-%m-%d-%H%M%S)"
RELEASE="${APP_ROOT}/releases/${STAMP}"
CURRENT="${APP_ROOT}/current"
SHARED="${APP_ROOT}/shared"
LOG="${APP_ROOT}/deploy.log"

cd "$APP_ROOT" || { echo "✗ APP_ROOT $APP_ROOT does not exist. Create it first."; exit 1; }
mkdir -p "${APP_ROOT}/releases" "${SHARED}/writable/uploads" "${SHARED}/writable/session" \
         "${SHARED}/writable/logs" "${SHARED}/writable/cache" "${SHARED}/writable/debugbar"

log() { printf '[%s] %s\n' "$(date +'%H:%M:%S')" "$*" | tee -a "$LOG"; }
fail() { log "✗ $*"; exit 1; }

log "════════════════════════════════════════════════════════════"
log "Deploy started · branch=$BRANCH · release=$STAMP"
log "════════════════════════════════════════════════════════════"

# ---- 1. Pre-deploy DB backup -------------------------------------------------
log "Step 1/8: pre-deploy DB backup"
"${APP_ROOT}/current/deploy/backup-db.sh" || log "⚠ backup-db.sh missing (first deploy?) — skipping"

# ---- 2. Clone the new release -----------------------------------------------
log "Step 2/8: cloning $BRANCH into releases/$STAMP"
git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$RELEASE" >> "$LOG" 2>&1 \
  || fail "git clone failed"

# ---- 3. Link shared resources (env + uploads + sessions + logs) -------------
log "Step 3/8: linking shared resources"
if [ ! -f "${SHARED}/.env" ]; then
    log "⚠ ${SHARED}/.env missing — copying .env.production.example as starter"
    cp "${RELEASE}/.env.production.example" "${SHARED}/.env"
    log "⚠ EDIT ${SHARED}/.env before traffic hits the new release"
fi
ln -sfn "${SHARED}/.env"               "${RELEASE}/.env"
rm -rf "${RELEASE}/writable/uploads" "${RELEASE}/writable/session" \
       "${RELEASE}/writable/logs"    "${RELEASE}/writable/cache"
ln -sfn "${SHARED}/writable/uploads"   "${RELEASE}/writable/uploads"
ln -sfn "${SHARED}/writable/session"   "${RELEASE}/writable/session"
ln -sfn "${SHARED}/writable/logs"      "${RELEASE}/writable/logs"
ln -sfn "${SHARED}/writable/cache"     "${RELEASE}/writable/cache"

# ---- 4. Composer install (no-dev, optimized autoloader) ---------------------
log "Step 4/8: composer install"
cd "$RELEASE"
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --prefer-dist >> "$LOG" 2>&1 \
  || fail "composer install failed"

# ---- 5. Database migrations (CI4 spark) --------------------------------------
log "Step 5/8: running database migrations"
$PHP_BIN spark migrate --all >> "$LOG" 2>&1 \
  || fail "migrations failed — investigate before flipping the symlink"

# ---- 6. Clear application caches --------------------------------------------
log "Step 6/8: clearing application caches"
$PHP_BIN spark cache:clear >> "$LOG" 2>&1 || true

# ---- 7. Permissions ---------------------------------------------------------
log "Step 7/8: setting permissions"
chown -R "$WEB_USER":"$WEB_USER" "$RELEASE/writable" "$SHARED/writable" "$RELEASE/public/assets"
chmod -R 775 "$RELEASE/writable" "$SHARED/writable"
chmod 640 "$SHARED/.env"

# ---- 8. Atomic symlink swap (the actual go-live) ----------------------------
log "Step 8/8: flipping current → $STAMP (atomic)"
ln -sfn "$RELEASE" "${CURRENT}.new" && mv -Tf "${CURRENT}.new" "$CURRENT" \
  || fail "symlink swap failed"

# ---- Reload PHP-FPM to drop OPcache (instant) ------------------------------
if command -v systemctl >/dev/null && systemctl is-active --quiet php8.2-fpm; then
    log "Reloading php8.2-fpm to drop OPcache"
    systemctl reload php8.2-fpm || log "⚠ php-fpm reload failed (you may need sudo)"
fi

# ---- Cleanup old releases ---------------------------------------------------
log "Pruning old releases (keeping last $KEEP_RELEASES)"
cd "${APP_ROOT}/releases"
ls -t | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf

log "✓ Deploy complete in $(( SECONDS ))s · current → $STAMP"
log "  Live at: $(grep app.baseURL "${SHARED}/.env" | head -1)"
log "  Rollback:  ./deploy/rollback.sh"
log "════════════════════════════════════════════════════════════"
