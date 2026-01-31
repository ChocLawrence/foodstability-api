#!/usr/bin/env bash
#
# Export database dump and storage/public files for deployment to a server.
# Run from project root: ./scripts/export-for-deployment.sh
#
# Output: deployment-export/YYYY-MM-DD_HH-mm/
#   - database.sql.gz      (MySQL dump)
#   - storage-app.tar.gz   (storage/app: images, PDFs, etc.)
#   - public-manuals.tar.gz (public/manuals: static PDFs)
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"
OUTPUT_BASE="$PROJECT_ROOT/deployment-export"
TIMESTAMP="$(date +%Y-%m-%d_%H-%M)"
OUTPUT_DIR="$OUTPUT_BASE/$TIMESTAMP"

cd "$PROJECT_ROOT"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Error: .env not found. Copy .env.example to .env and set DB_* and paths." >&2
  exit 1
fi

# Read DB_* from .env (value may contain '=', so we use cut only for first =)
get_env() {
  local key="$1"
  grep -E "^${key}=" "$ENV_FILE" 2>/dev/null | cut -d= -f2- | sed -e 's/^["'\'']//' -e 's/["'\'']$//' -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' || true
}

DB_CONNECTION="$(get_env DB_CONNECTION)"
DB_HOST="$(get_env DB_HOST)"
DB_PORT="$(get_env DB_PORT)"
DB_DATABASE="$(get_env DB_DATABASE)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"

if [[ -z "$DB_DATABASE" ]]; then
  echo "Error: DB_DATABASE not set in .env" >&2
  exit 1
fi

mkdir -p "$OUTPUT_DIR"

# --- 1. Database dump (MySQL) ---
echo "Dumping database: $DB_DATABASE"
if [[ "$DB_CONNECTION" == "mysql" ]]; then
  MYSQL_PWD="$DB_PASSWORD" mysqldump \
    -h "${DB_HOST:-127.0.0.1}" \
    -P "${DB_PORT:-3306}" \
    -u "$DB_USERNAME" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    "$DB_DATABASE" 2>/dev/null | gzip -9 > "$OUTPUT_DIR/database.sql.gz"
  echo "  -> $OUTPUT_DIR/database.sql.gz"
elif [[ "$DB_CONNECTION" == "pgsql" ]]; then
  export PGPASSWORD="$DB_PASSWORD"
  pg_dump -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" -U "$DB_USERNAME" "$DB_DATABASE" | gzip -9 > "$OUTPUT_DIR/database.sql.gz"
  echo "  -> $OUTPUT_DIR/database.sql.gz"
else
  echo "Warning: Unsupported DB_CONNECTION=$DB_CONNECTION. Skipping database dump." >&2
fi

# --- 2. Storage (app) — images, PDFs, all uploads under storage/app ---
echo "Archiving storage/app (images, PDFs, uploads)..."
tar -czf "$OUTPUT_DIR/storage-app.tar.gz" -C "$PROJECT_ROOT" storage/app 2>/dev/null || true
echo "  -> $OUTPUT_DIR/storage-app.tar.gz"

# --- 3. Public manuals (static PDFs in public/manuals) ---
if [[ -d "$PROJECT_ROOT/public/manuals" ]]; then
  echo "Archiving public/manuals..."
  tar -czf "$OUTPUT_DIR/public-manuals.tar.gz" -C "$PROJECT_ROOT" public/manuals
  echo "  -> $OUTPUT_DIR/public-manuals.tar.gz"
else
  echo "Skipping public/manuals (directory not found)."
fi

echo ""
echo "Export complete: $OUTPUT_DIR"
echo "  - database.sql.gz"
echo "  - storage-app.tar.gz"
echo "  - public-manuals.tar.gz (if present)"
echo ""
echo "To deploy on the server, see DEPLOYMENT.md (Restore steps)."
