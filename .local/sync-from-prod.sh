#!/usr/bin/env bash
# Быстрый сценарий: remote БД + недостающие картинки с prod.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "=== 1/2 Remote MySQL ==="
"$ROOT/.local/use-db-remote.sh"

echo ""
echo "=== 2/2 wa-data (missing product dirs + webp) ==="
"$ROOT/.local/sync-wa-data-from-prod.sh" missing

echo ""
echo "=== Опционально: догнать webp для старых товаров ==="
echo "  ./.local/sync-wa-data-from-prod.sh webp"
