#!/usr/bin/env bash
# Облегчённый local dev: удалить тяжёлые wa-data, картинки — с prod через nginx proxy.
#
# Usage:
#   ./.local/setup-light-dev.sh              # интерактивно
#   ./.local/setup-light-dev.sh --yes        # без подтверждения
#   ./.local/setup-light-dev.sh --dry-run    # только показать, что будет удалено
#
# После:
#   ./start-dev.sh
#   ./.local/use-db-remote.sh   # опционально: боевая БД без локального дампа
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PHP_BIN="${PHP_BIN:-/opt/homebrew/opt/php@7.2/bin/php}"
DRY_RUN=0
ASSUME_YES=0

usage() {
  sed -n '2,12p' "$0" | sed 's/^# \?//'
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift ;;
    --yes|-y) ASSUME_YES=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown: $1" >&2; usage >&2; exit 1 ;;
  esac
done

# Только медиа/uploads — темы и код не трогаем
HEAVY_PATHS=(
  wa-data/public/shop/products
  wa-data/public/shop/img
  wa-data/public/shop/brands
  wa-data/public/shop/slider
  wa-data/public/shop/wmimageincatPlugin
  wa-data/public/shop/plugins
  wa-data/public/shop/promos
  wa-data/public/shop/easyinvoicephys
  wa-data/protected/shop
  wa-cache
  wa-log
)

KEEP_PATHS=(
  wa-data/public/shop/themes
  wa-data/public/site/themes
  wa-data/public/blog/themes
)

human_size() {
  du -sh "$1" 2>/dev/null | awk '{print $1}'
}

echo "=== almamed: облегчённый local dev ==="
echo "Проект: $ROOT"
echo ""

TOTAL_BEFORE="$(human_size "$ROOT")"
WADATA_BEFORE="$(human_size "$ROOT/wa-data" 2>/dev/null || echo '?')"
echo "Сейчас: проект $TOTAL_BEFORE, wa-data $WADATA_BEFORE"
echo ""

echo "Будут удалены (если есть):"
TO_FREE=0
for rel in "${HEAVY_PATHS[@]}"; do
  if [[ -e "$ROOT/$rel" ]]; then
    sz="$(du -sk "$ROOT/$rel" 2>/dev/null | awk '{print $1}')"
    TO_FREE=$((TO_FREE + sz))
    printf "  %-45s %s\n" "$rel" "$(human_size "$ROOT/$rel")"
  fi
done
echo "≈ $(echo "$TO_FREE" | awk '{printf "%.1f GB", $1/1024/1024}') можно освободить"
echo ""

echo "Останутся обязательно:"
for rel in "${KEEP_PATHS[@]}"; do
  if [[ -d "$ROOT/$rel" ]]; then
    printf "  ✓ %-45s %s\n" "$rel" "$(human_size "$ROOT/$rel")"
  else
    printf "  ✗ %-45s (НЕТ — восстановите из git: git checkout -- %s)\n" "$rel" "$rel"
  fi
done
echo ""

if [[ "$DRY_RUN" -eq 1 ]]; then
  echo "Dry-run: ничего не удалено."
  exit 0
fi

if [[ "$ASSUME_YES" -ne 1 ]]; then
  read -r -p "Удалить перечисленное локально? [y/N] " ans
  case "$ans" in
    y|Y|yes|YES) ;;
    *) echo "Отменено."; exit 0 ;;
  esac
fi

for rel in "${HEAVY_PATHS[@]}"; do
  if [[ -e "$ROOT/$rel" ]]; then
    rm -rf "$ROOT/$rel"
    echo "  удалено: $rel"
  fi
done

mkdir -p "$ROOT/.local/run"
touch "$ROOT/.local/run/light-dev.mode"

discover_proxy_test_path() {
  local path=""

  if [[ -f "$ROOT/wa-config/db.php" ]] && "$PHP_BIN" -r "exit(class_exists('mysqli')?0:1);" 2>/dev/null; then
    path="$("$PHP_BIN" -r "
      try {
        require_once '$ROOT/wa-config/SystemConfig.class.php';
        \$config = new SystemConfig('cli');
        waSystem::getInstance(null, \$config);
        wa('shop', 1);
        \$row = (new shopProductImagesModel())->query(
          'SELECT product_id, id, filename, ext FROM shop_product_images ORDER BY id DESC LIMIT 1'
        )->fetchAssoc();
        if (!\$row || empty(\$row['filename'])) { exit(0); }
        \$id = (int)\$row['product_id'];
        \$str = str_pad((string)\$id, 4, '0', STR_PAD_LEFT);
        echo '/wa-data/public/shop/products/' . substr(\$str, -2) . '/' . substr(\$str, -4, 2) . '/' . \$id . '/' . \$row['id'] . '/' . \$row['filename'] . '.' . \$row['ext'] . '/';
      } catch (Throwable \$e) {
        exit(0);
      }
    " 2>/dev/null || true)"
  fi

  if [[ -z "$path" ]]; then
    ENV_FILE="$ROOT/.local/sync-prod.env"
    if [[ -f "$ENV_FILE" ]]; then
      # shellcheck disable=SC1090
      source "$ENV_FILE"
    fi
    PROD_SSH="${PROD_SSH:-root@45.90.35.63}"
    PROD_PATH="${PROD_PATH:-/var/www/almamed.su/data/www/almamed.su/}"
    path="$(ssh -o BatchMode=yes -o ConnectTimeout=15 "$PROD_SSH" \
      "find '${PROD_PATH}wa-data/public/shop/products' -type f \\( -name '*.jpg' -o -name '*.webp' -o -name '*.png' \\) 2>/dev/null | head -1 | sed 's|^${PROD_PATH}||'" \
      2>/dev/null || true)"
  fi

  if [[ -n "$path" ]]; then
    [[ "$path" != /* ]] && path="/$path"
    echo "$path" > "$ROOT/.local/run/media-proxy-test.path"
    echo "  тест proxy: $path"
  else
    rm -f "$ROOT/.local/run/media-proxy-test.path"
    echo "  тест proxy: путь не найден (проверка при start-dev будет пропущена)"
  fi
}

discover_proxy_test_path

TOTAL_AFTER="$(human_size "$ROOT")"
WADATA_AFTER="$(human_size "$ROOT/wa-data" 2>/dev/null || echo '?')"

echo ""
echo "Готово: проект $TOTAL_BEFORE → $TOTAL_AFTER, wa-data $WADATA_BEFORE → $WADATA_AFTER"
echo ""
echo "Картинки и uploads без локальной копии отдаются с prod (nginx proxy → almamed.su)."
echo "Темы по-прежнему локальные (из git)."
echo ""
echo "Дальше:"
echo "  ./start-dev.sh"
echo "  ./.local/use-db-remote.sh          # опционально: prod БД"
echo "  ./.local/sync-wa-data-from-prod.sh missing   # точечно, если нужен офлайн"
echo ""
