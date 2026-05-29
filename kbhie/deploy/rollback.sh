#!/usr/bin/env bash
#==============================================================================
#  Khoobie instant rollback
#  Flip 'current' symlink to the previous release. Zero downtime.
#  Use when a deploy went wrong and you need to undo it NOW.
#
#  Usage:
#    ./deploy/rollback.sh                   # flip to previous release
#    ./deploy/rollback.sh 2026-05-26-093045 # flip to a specific release
#==============================================================================

set -euo pipefail
APP_ROOT="${APP_ROOT:-/var/www/khoobie}"
RELEASES="${APP_ROOT}/releases"
CURRENT="${APP_ROOT}/current"

cd "$RELEASES" || { echo "✗ $RELEASES not found"; exit 1; }

if [ -n "${1:-}" ]; then
    TARGET="$1"
else
    # Most recent release that is NOT the currently-symlinked one
    CURRENT_REL="$(basename "$(readlink "$CURRENT")")"
    TARGET="$(ls -t | grep -v "^$CURRENT_REL$" | head -1)"
fi

[ -d "$TARGET" ] || { echo "✗ Target release $TARGET does not exist"; exit 1; }

echo "Rolling back: current → $TARGET"
ln -sfn "${RELEASES}/${TARGET}" "${CURRENT}.new" && mv -Tf "${CURRENT}.new" "$CURRENT"

# Reload PHP-FPM to drop OPcache (instant)
if command -v systemctl >/dev/null && systemctl is-active --quiet php8.2-fpm; then
    systemctl reload php8.2-fpm || echo "⚠ php-fpm reload failed — restart manually"
fi

echo "✓ Rolled back to $TARGET in <1 second."
echo ""
echo "If the rollback was caused by a bad DB migration, also restore from backup:"
echo "  ls -t ${APP_ROOT}/backups/ | head"
echo "  mysql -uroot khoobie < ${APP_ROOT}/backups/<latest>.sql"
