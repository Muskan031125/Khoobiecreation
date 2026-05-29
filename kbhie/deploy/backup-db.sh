#!/usr/bin/env bash
#==============================================================================
#  Khoobie database backup — runs daily via cron + before every deploy
#  Keeps 30 daily backups. Compressed gzip. ~200KB per backup of full DB.
#
#  Cron entry:
#    0 3 * * * /var/www/khoobie/current/deploy/backup-db.sh >> /var/log/khoobie-backup.log 2>&1
#==============================================================================

set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/khoobie}"
BACKUP_DIR="${APP_ROOT}/backups"
KEEP_DAYS=30

# Read DB creds from the shared .env (single source of truth)
ENV_FILE="${APP_ROOT}/shared/.env"
[ -f "$ENV_FILE" ] || { echo "✗ $ENV_FILE not found"; exit 1; }

DB_HOST=$(grep -E "^database\.default\.hostname" "$ENV_FILE" | sed -E "s/.*=\s*'?([^']+)'?\s*/\1/")
DB_PORT=$(grep -E "^database\.default\.port"     "$ENV_FILE" | sed -E "s/.*=\s*'?([^']+)'?\s*/\1/")
DB_NAME=$(grep -E "^database\.default\.database" "$ENV_FILE" | sed -E "s/.*=\s*'?([^']+)'?\s*/\1/")
DB_USER=$(grep -E "^database\.default\.username" "$ENV_FILE" | sed -E "s/.*=\s*'?([^']+)'?\s*/\1/")
DB_PASS=$(grep -E "^database\.default\.password" "$ENV_FILE" | sed -E "s/.*=\s*'?([^']+)'?\s*/\1/")

mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y-%m-%d-%H%M%S)"
OUT="${BACKUP_DIR}/khoobie-${STAMP}.sql.gz"

echo "[$(date)] Backing up ${DB_NAME} from ${DB_HOST}:${DB_PORT} → $(basename "$OUT")"

# --routines: stored procedures · --triggers: triggers · --events: scheduled events
# --single-transaction: consistent snapshot without locking InnoDB · --quick: stream rows
mysqldump \
  -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
  --routines --triggers --events \
  --single-transaction --quick --hex-blob \
  --default-character-set=utf8mb4 \
  --skip-comments --no-tablespaces \
  "$DB_NAME" | gzip -9 > "$OUT"

SIZE=$(du -h "$OUT" | cut -f1)
echo "[$(date)] ✓ Backup written ($SIZE)"

# Prune old backups
find "$BACKUP_DIR" -name "khoobie-*.sql.gz" -type f -mtime +"$KEEP_DAYS" -delete
REMAINING=$(ls -1 "$BACKUP_DIR"/khoobie-*.sql.gz 2>/dev/null | wc -l)
echo "[$(date)] Retention: $REMAINING backups kept (last $KEEP_DAYS days)"

# OPTIONAL: also upload to S3 / R2 / Backblaze for off-site safety
# Uncomment when you've set up `aws` CLI with the right credentials:
# aws s3 cp "$OUT" "s3://khoobie-backups/db/$(basename "$OUT")" --storage-class STANDARD_IA
