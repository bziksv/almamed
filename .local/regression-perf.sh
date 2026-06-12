#!/usr/bin/env bash
# Фаза 9 — автоматические замеры perf (local или prod через BASE_URL / Host)
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
HOST_HEADER="${HOST_HEADER:-}"

curl_ttfb() {
  local label="$1"
  local url="$2"
  if [[ -n "$HOST_HEADER" ]]; then
    curl -sS -o /dev/null -H "Host: $HOST_HEADER" -w "${label}: HTTP %{http_code} ttfb=%{time_starttransfer}s total=%{time_total}s\n" "${BASE_URL}${url}"
  else
    curl -sS -o /dev/null -w "${label}: HTTP %{http_code} ttfb=%{time_starttransfer}s total=%{time_total}s\n" "${BASE_URL}${url}"
  fi
}

curl_total() {
  local label="$1"
  local url="$2"
  if [[ -n "$HOST_HEADER" ]]; then
    curl -sS -o /dev/null -H "Host: $HOST_HEADER" -w "${label}: HTTP %{http_code} total=%{time_total}s\n" "${BASE_URL}${url}"
  else
    curl -sS -o /dev/null -w "${label}: HTTP %{http_code} total=%{time_total}s\n" "${BASE_URL}${url}"
  fi
}

echo "=== almamed perf regression ==="
echo "BASE_URL=$BASE_URL${HOST_HEADER:+ Host=$HOST_HEADER}"
echo

curl_ttfb "home cold" "/"
curl_ttfb "home warm" "/"
curl -sS -D - -o /dev/null "${BASE_URL}/" | grep -i x-shop-cache || echo "home cache header: (miss)"
curl -sS -D - -o /dev/null "${BASE_URL}/" | grep -i x-shop-cache || true
curl_ttfb "category cold" "/category/ginekologiya/"
curl_ttfb "category warm" "/category/ginekologiya/"
curl_ttfb "search cold" "/search/%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF/"
curl_ttfb "search warm" "/search/%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF/"
curl_total "suggest cold" "/searchpro-plugin/suggest/?q=%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF"
curl_total "suggest warm" "/searchpro-plugin/suggest/?q=%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF"
if [[ -n "$HOST_HEADER" ]]; then
  curl -sS -D - -o /dev/null -H "Host: $HOST_HEADER" "${BASE_URL}/category/ginekologiya/" | grep -i x-shop-cache || echo "category cache header: (miss or nginx strips)"
  curl -sS -D - -o /dev/null -H "Host: $HOST_HEADER" "${BASE_URL}/category/ginekologiya/" | grep -i x-shop-cache || true
  curl -sS -o /dev/null -H "Host: $HOST_HEADER" -w "brands: HTTP %{http_code} size=%{size_download}B ttfb=%{time_starttransfer}s\n" "${BASE_URL}/brands/"
else
  curl -sS -D - -o /dev/null "${BASE_URL}/category/ginekologiya/" | grep -i x-shop-cache || echo "category cache header: (miss or nginx strips)"
  curl -sS -D - -o /dev/null "${BASE_URL}/category/ginekologiya/" | grep -i x-shop-cache || true
  curl -sS -o /dev/null -w "brands: HTTP %{http_code} size=%{size_download}B ttfb=%{time_starttransfer}s\n" "${BASE_URL}/brands/"
fi
curl_ttfb "seofilter 404" "/category/ginekologiya/filter/nesushchestvuyushchiy/"
echo
echo "=== smoke HTTP codes ==="
for path in "/" "/category/veterinariya/" "/brands/" "/dostavka_i_oplata/"; do
  if [[ -n "$HOST_HEADER" ]]; then
    code=$(curl -sS -o /dev/null -w "%{http_code}" -H "Host: $HOST_HEADER" "${BASE_URL}${path}")
  else
    code=$(curl -sS -o /dev/null -w "%{http_code}" "${BASE_URL}${path}")
  fi
  echo "${path} → ${code}"
done
