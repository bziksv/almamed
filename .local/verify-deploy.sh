#!/usr/bin/env bash
# Post-deploy smoke: perf + HTML markers (fonts, slider, SearchPro).
set -euo pipefail

BASE_URL="${BASE_URL:-https://almamed.su}"
HOST_HEADER="${HOST_HEADER:-}"
CURL_OPTS=(--http1.1 -sS -L --max-time 30)
if [[ -n "$HOST_HEADER" ]]; then
  CURL_OPTS+=(-H "Host: ${HOST_HEADER}")
fi
if [[ "$BASE_URL" == https://* ]] && [[ "$BASE_URL" == *"://[0-9]"* || "$BASE_URL" == *"213.139"* ]]; then
  CURL_OPTS+=(-k)
fi

fetch() {
  curl "${CURL_OPTS[@]}" "${BASE_URL}$1"
}

echo "=== verify-deploy ==="
echo "BASE_URL=$BASE_URL${HOST_HEADER:+ Host=$HOST_HEADER}"
echo

./.local/regression-perf.sh

echo
echo "=== HTML markers (category) ==="
CAT=$(fetch "/category/ginekologiya/")
for marker in "font-awesome" "frontend.fonts.css" "lightslider" "Searchpro-Roboto" "Roboto:400,500,700"; do
  count=$(echo "$CAT" | grep -c "$marker" || true)
  echo "$marker: $count"
done

echo
echo "=== HTML markers (home) ==="
HOME=$(fetch "/")
for marker in "lightslider" "header-slider-wrap"; do
  count=$(echo "$HOME" | grep -c "$marker" || true)
  echo "$marker: $count"
done

THEME_VER=$(grep -oE 'version="[0-9]+\.[0-9]+\.[0-9]+"' \
  wa-data/public/site/themes/osnovnaja_new_header_footer_form/theme.xml | head -1 | tr -d 'version="')
LIVE=$(echo "$HOME" | grep -oE 'profitbuy\.min\.css\?v[0-9.]+' | head -1 || true)
echo "theme.xml: v${THEME_VER:-?}  live CSS: ${LIVE:-not found}"

echo
echo "=== SearchPro helper (popular) ==="
fetch "/searchpro-plugin/popular/" | head -c 120
echo

echo "=== done ==="
