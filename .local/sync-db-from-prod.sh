#!/usr/bin/env bash
# Свежий дамп prod MySQL → local almamed_su_db.
# Требует: local MySQL 8.0, доступ к 45.90.35.63 (user cursor).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

MYSQL8=/opt/homebrew/opt/mysql@8.0/bin/mysql
MYSQLDUMP8=/opt/homebrew/opt/mysql@8.0/bin/mysqldump
DUMP="$ROOT/.db-dump/almamed_su_db.sql.gz"
REMOTE=( -h 45.90.35.63 -u cursor -p'4n6Tb4YttIJqyDkl' )
LOCAL=( -u almamed -plocaldev almamed_su_db )

mkdir -p "$ROOT/.db-dump"

echo "→ 1/4 Переключение на local db.php"
"$ROOT/.local/use-db-local.sh" | sed 's/^/  /'

echo ""
echo "→ 2/4 mysqldump prod (может занять 10–15 мин)…"
"$MYSQLDUMP8" "${REMOTE[@]}" --single-transaction --quick --routines --triggers almamed_su_db \
  | gzip > "$DUMP"
ls -lh "$DUMP"

echo ""
echo "→ 3/4 Импорт в local MySQL…"
gunzip -c "$DUMP" | "$MYSQL8" "${LOCAL[@]}"
COUNT=$("$MYSQL8" "${LOCAL[@]}" -N -e "SELECT COUNT(*) FROM shop_product")
echo "  shop_product=$COUNT"

echo ""
echo "→ 4/4 clearCache"
php cli.php webasyst clearCache >/dev/null 2>&1 || true
echo "  OK"
echo ""
echo "Готово. Дамп: $DUMP"
