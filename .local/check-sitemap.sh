#!/usr/bin/env bash
# Sitemap size / structure smoke (local or prod).
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
HOST_HEADER="${HOST_HEADER:-}"
CURL_OPTS=(--http1.1 -sS -L --max-time 60)
if [[ -n "$HOST_HEADER" ]]; then
  CURL_OPTS+=(-H "Host: ${HOST_HEADER}")
fi
if [[ "$BASE_URL" == https://* ]] && [[ "$BASE_URL" == *213.139* || "$BASE_URL" == *45.90* ]]; then
  CURL_OPTS+=(-k)
fi

fetch() {
  curl "${CURL_OPTS[@]}" "${BASE_URL}$1"
}

size_bytes() {
  curl "${CURL_OPTS[@]}" -o /dev/null -w "%{size_download}" "${BASE_URL}$1"
}

count_loc() {
  fetch "$1" | grep -c '<loc>' || true
}

echo "=== sitemap check ==="
echo "BASE_URL=$BASE_URL${HOST_HEADER:+ Host=$HOST_HEADER}"
echo

for path in /sitemap.xml /sitemap-shop-1.xml /filter-sitemap.xml; do
  bytes=$(size_bytes "$path" 2>/dev/null || echo 0)
  locs=$(count_loc "$path" 2>/dev/null || echo 0)
  kb=$(echo "scale=1; $bytes/1024" | bc 2>/dev/null || echo "?")
  echo "${path}: ${kb} KB, loc≈${locs}"
done

echo
echo "--- filter-sitemap in root index? ---"
if fetch /sitemap.xml | grep -q 'filter-sitemap'; then
  echo "yes — filter-sitemap linked from /sitemap.xml"
  fetch /sitemap.xml | grep -o '<loc>[^<]*filter-sitemap[^<]*</loc>' | head -3
else
  echo "no — check seofilter use_sitemap_hook=1 and plugin enabled"
fi

echo
echo "--- robots.txt Sitemap lines ---"
robots=$(fetch /robots.txt 2>/dev/null || true)
if echo "$robots" | grep -i '^Sitemap:' | head -5; then
  :
else
  echo "(no Sitemap: lines in robots.txt)"
fi

echo
echo "--- seofilter pages (sample) ---"
fetch /filter-sitemap.xml 2>/dev/null | grep -o '<loc>[^<]*</loc>' | head -3 || echo "(filter-sitemap unavailable)"

echo "=== done ==="
