#!/usr/bin/env bash
# Синхронизация wa-data (картинки, webp, файлы товаров) с prod → local.
#
# Usage:
#   ./.local/sync-wa-data-from-prod.sh products          # товары + webp (основной режим)
#   ./.local/sync-wa-data-from-prod.sh shop             # products + img + brands + promos …
#   ./.local/sync-wa-data-from-prod.sh missing           # только папки товаров из БД, которых нет local
#   ./.local/sync-wa-data-from-prod.sh recent            # последние 50 товаров ( --limit N )
#   ./.local/sync-wa-data-from-prod.sh webp              # только *.webp под products/
#   ./.local/sync-wa-data-from-prod.sh wa-data           # весь wa-data (~30+ GB)
#
# Перед первым запуском:
#   cp .local/sync-prod.env.example .local/sync-prod.env
#   ssh root@45.90.35.63   # проверить доступ
#
# Рекомендуется вместе с remote БД:
#   ./.local/use-db-remote.sh && ./.local/sync-wa-data-from-prod.sh missing
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

ENV_FILE="$ROOT/.local/sync-prod.env"
[[ -f "$ENV_FILE" ]] && # shellcheck disable=SC1090
  source "$ENV_FILE"

PROD_SSH="${PROD_SSH:-root@45.90.35.63}"
PROD_PATH="${PROD_PATH:-/var/www/almamed.su/data/www/almamed.su/}"
RSYNC_BIN="${RSYNC_BIN:-$(command -v rsync || echo /opt/homebrew/bin/rsync)}"
PHP_BIN="${PHP_BIN:-/opt/homebrew/opt/php@7.2/bin/php}"

MODE="products"
DELETE=""
RECENT_LIMIT=50

usage() {
  sed -n '2,12p' "$0" | sed 's/^# \?//'
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    products|shop|wa-data|missing|recent|webp)
      MODE="$1"
      shift
      ;;
    --delete)
      DELETE="--delete"
      shift
      ;;
    --limit)
      RECENT_LIMIT="${2:?--limit requires number}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

REMOTE="${PROD_SSH}:${PROD_PATH}"
RSYNC_OPTS=(-a -c -z --info=progress2 --stats --human-readable)

product_rel_path() {
  "$PHP_BIN" -r '
    $id = (int)($argv[1] ?? 0);
    if ($id <= 0) { exit(1); }
    $str = str_pad((string)$id, 4, "0", STR_PAD_LEFT);
    echo "wa-data/public/shop/products/" . substr($str, -2) . "/" . substr($str, -4, 2) . "/" . $id . "/";
  ' "$1"
}

remote_dir_exists() {
  ssh -o BatchMode=yes -o ConnectTimeout=20 "$PROD_SSH" "test -d '${PROD_PATH}$1'"
}

check_ssh() {
  echo "→ SSH $PROD_SSH, path ${PROD_PATH}"
  if ! ssh -o BatchMode=yes -o ConnectTimeout=20 "$PROD_SSH" "test -d '${PROD_PATH}wa-data/public/shop/products'"; then
    cat >&2 <<EOF

SSH/rsync к prod недоступен.

1. cp .local/sync-prod.env.example .local/sync-prod.env
2. ssh-copy-id $PROD_SSH
3. ssh $PROD_SSH 'ls ${PROD_PATH}wa-data/public/shop/products | head'

EOF
    exit 1
  fi
  echo "  OK"
}

rsync_rel() {
  local rel="${1%/}/"
  if ! remote_dir_exists "$rel"; then
    echo "  skip (нет на prod): $rel"
    return 0
  fi
  echo ""
  echo "=== rsync $rel ==="
  # shellcheck disable=SC2086
  "$RSYNC_BIN" "${RSYNC_OPTS[@]}" $DELETE \
    "${REMOTE}${rel}" "${ROOT}/${rel}"
}

rsync_product_paths() {
  local -a paths=("$@")
  if [[ ${#paths[@]} -eq 0 ]]; then
    echo "  Нечего синхронизировать."
    return 0
  fi
  echo ""
  echo "=== rsync ${#paths[@]} product dir(s) ==="
  local p rel
  for p in "${paths[@]}"; do
    rel="$(product_rel_path "$p")"
    if ! remote_dir_exists "$rel"; then
      echo "  skip id=$p (нет папки на prod): $rel"
      continue
    fi
    # shellcheck disable=SC2086
    "$RSYNC_BIN" "${RSYNC_OPTS[@]}" $DELETE \
      "${REMOTE}${rel}" "${ROOT}/${rel}"
  done
}

query_product_ids() {
  local sql="$1"
  "$PHP_BIN" -r '
    $root = $argv[1];
    $sql = $argv[2];
    $c = include $root . "/wa-config/db.php";
    $d = $c["default"];
    $m = new mysqli($d["host"], $d["user"], $d["password"], $d["database"]);
    if ($m->connect_error) {
      fwrite(STDERR, $m->connect_error . PHP_EOL);
      exit(1);
    }
    $m->set_charset("utf8");
    $r = $m->query($sql);
    if (!$r) {
      fwrite(STDERR, $m->error . PHP_EOL);
      exit(1);
    }
    while ($row = $r->fetch_assoc()) {
      echo $row["id"] . "\n";
    }
  ' "$ROOT" "$sql"
}

find_missing_product_ids() {
  "$PHP_BIN" -r '
    $root = $argv[1];
    $c = include $root . "/wa-config/db.php";
    $d = $c["default"];
    $m = new mysqli($d["host"], $d["user"], $d["password"], $d["database"]);
    if ($m->connect_error) {
      fwrite(STDERR, $m->connect_error . PHP_EOL);
      exit(1);
    }
    $r = $m->query("SELECT id FROM shop_product ORDER BY id");
    while ($row = $r->fetch_assoc()) {
      $id = (int)$row["id"];
      $str = str_pad((string)$id, 4, "0", STR_PAD_LEFT);
      $rel = "wa-data/public/shop/products/" . substr($str, -2) . "/" . substr($str, -4, 2) . "/" . $id;
      if (!is_dir($root . "/" . $rel)) {
        echo $id . "\n";
      }
    }
  ' "$ROOT"
}

check_ssh

case "$MODE" in
  products)
    rsync_rel "wa-data/public/shop/products"
    ;;
  shop)
    for d in products img brands promos plugins data slider wmimageincatPlugin; do
      rsync_rel "wa-data/public/shop/${d}"
    done
    for d in shop site blog; do
      rsync_rel "wa-data/public/${d}/themes"
    done
    rsync_rel "wa-data/protected/shop"
    ;;
  wa-data)
    echo ""
    echo "=== rsync wa-data/ (полный, может занять долго) ==="
    # shellcheck disable=SC2086
    "$RSYNC_BIN" "${RSYNC_OPTS[@]}" $DELETE \
      "${REMOTE}wa-data/" "${ROOT}/wa-data/"
    ;;
  webp)
    echo ""
    echo "=== rsync только *.webp в products/ ==="
    # shellcheck disable=SC2086
    "$RSYNC_BIN" "${RSYNC_OPTS[@]}" $DELETE \
      --include='*/' --include='*.webp' --exclude='*' \
      "${REMOTE}wa-data/public/shop/products/" \
      "${ROOT}/wa-data/public/shop/products/"
    ;;
  missing)
    mapfile -t MISSING < <(find_missing_product_ids)
    echo "→ В БД, но нет папки local: ${#MISSING[@]} товар(ов)"
    rsync_product_paths "${MISSING[@]}"
    ;;
  recent)
    mapfile -t RECENT < <(query_product_ids "SELECT id FROM shop_product ORDER BY id DESC LIMIT ${RECENT_LIMIT}")
    echo "→ Последние ${RECENT_LIMIT} товаров по id"
    rsync_product_paths "${RECENT[@]}"
    ;;
esac

WEBP_COUNT="$(find "${ROOT}/wa-data/public/shop/products" -name '*.webp' 2>/dev/null | wc -l | tr -d ' ')"
echo ""
echo "→ Local webp files: ${WEBP_COUNT}"

php cli.php webasyst clearCache >/dev/null 2>&1 || true
echo "→ cache cleared"
echo ""
echo "Готово. Local dev: http://localhost:8080/ (убедитесь что ./.local/use-db-remote.sh уже включён)."
