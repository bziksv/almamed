#!/usr/bin/env bash
# Smoke UI markers — перед/после jQuery defer и деплоя PageSpeed.
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
HOST_HEADER="${HOST_HEADER:-}"
FAIL=0
TMPDIR="${TMPDIR:-/tmp}"

fetch() {
  local path="$1"
  local out="$2"
  if [[ -n "$HOST_HEADER" ]]; then
    curl -sS -H "Host: $HOST_HEADER" "${BASE_URL}${path}" -o "$out"
  else
    curl -sS "${BASE_URL}${path}" -o "$out"
  fi
}

check_file() {
  local label="$1"
  local file="$2"
  local pattern="$3"
  if grep -qE "$pattern" "$file"; then
    echo "OK  $label"
  else
    echo "FAIL $label (pattern: $pattern)"
    FAIL=1
  fi
}

HOME_FILE="$(mktemp "${TMPDIR}/almamed-home.XXXXXX")"
CAT_FILE="$(mktemp "${TMPDIR}/almamed-cat.XXXXXX")"
SEARCH_FILE="$(mktemp "${TMPDIR}/almamed-search.XXXXXX")"
trap 'rm -f "$HOME_FILE" "$CAT_FILE" "$SEARCH_FILE"' EXIT

echo "=== almamed UI regression ==="
echo "BASE_URL=$BASE_URL${HOST_HEADER:+ Host=$HOST_HEADER}"
echo

fetch / "$HOME_FILE"
fetch /category/ginekologiya/ "$CAT_FILE"
fetch /search/%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF/ "$SEARCH_FILE"

check_file "home HTTP body" "$HOME_FILE" 'Лучшие предложения'
check_file "home sidebar" "$HOME_FILE" 'Категории'
check_file "home search field" "$HOME_FILE" 'Поиск по категориям'
if grep -q 'Все категории' "$HOME_FILE"; then
  echo "FAIL home still has «Все категории» dropdown"
  FAIL=1
else
  echo "OK  home no «Все категории» dropdown"
fi
check_file "home jquery loaded" "$HOME_FILE" 'jquery-1\.11\.1'
check_file "home cart widget js" "$HOME_FILE" 'refreshCartWidget'
check_file "category product list" "$CAT_FILE" 'product-list'
check_file "search results" "$SEARCH_FILE" 'product-list|searchpro'

if grep -q '<script[^>]*src="[^"]*jquery[^"]*"[^>]*defer' "$HOME_FILE"; then
  if grep -E '<script[^>]*>[^<]*\$\(' "$HOME_FILE" | grep -qv defer; then
    echo "WARN inline \$() without defer — may break after jQuery defer"
    FAIL=1
  else
    echo "OK  jQuery defer + no obvious sync inline \$()"
  fi
fi

echo
if [[ $FAIL -eq 0 ]]; then
  echo "=== all checks passed ==="
else
  echo "=== some checks failed ==="
  exit 1
fi
