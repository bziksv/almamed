#!/usr/bin/env bash
# После rsync или git pull — включить плагины, сбросить кэш, прогреть.
set -euo pipefail

cd "$(dirname "$0")/../.."
OWNER="${DEPLOY_OWNER:-almamed.su}"

run_as_owner() {
  if [[ "$(id -un)" == "$OWNER" ]]; then
    "$@"
  else
    sudo -u "$OWNER" "$@"
  fi
}

echo "=== post-deploy PageSpeed ==="

if [[ -x .local/bump-theme-edition.php ]] || [[ -f .local/bump-theme-edition.php ]]; then
  echo "--- bump theme edition (JS/CSS ?v=) ---"
  run_as_owner php .local/bump-theme-edition.php
fi

THEME_VER=$(grep -oE 'version="[0-9]+\.[0-9]+\.[0-9]+"' \
  wa-data/public/site/themes/osnovnaja_new_header_footer_form/theme.xml | head -1 | tr -d 'version="')
SITE_ED=$(grep -oE 'edition="[0-9]+"' \
  wa-data/public/site/themes/osnovnaja_new_header_footer_form/theme.xml | head -1 | tr -d 'edition="')
SHOP_ED=$(grep -oE 'edition="[0-9]+"' \
  wa-data/public/shop/themes/osnovnaja_new_header_footer_form/theme.xml | head -1 | tr -d 'edition="')
COMBINED_ED=$(( ${SITE_ED:-0} + ${SHOP_ED:-0} ))
echo "theme cache-bust: v${THEME_VER:-?}.${COMBINED_ED}"

# WebP plugin autoload
run_as_owner php -r "
require 'wa-config/SystemConfig.class.php';
\$c = new SystemConfig('cli');
waSystem::getInstance(null, \$c);
waSystem::getInstance('shop', null, true);
if (class_exists('shopConfig')) {
    shopConfig::clearAutoloadCache('shop');
    echo \"autoload cache cleared\n\";
}
"

run_as_owner php cli.php webasyst clearCache
find wa-cache -mindepth 1 -delete 2>/dev/null || true

echo "--- optional: slider WebP (once, ~2 min) ---"
echo "  php cli.php shop sliderPluginGenerateResponsive"

echo "--- optional: catalog WebP batch (long, on-demand works) ---"
echo "  php cli.php shop webpimagesPluginGenerate"

echo "--- warm cache ---"
BASE="${BASE_URL:-http://127.0.0.1}"
HOST="${HOST_HEADER:+-H Host: $HOST_HEADER}"
for path in "/" "/category/ginekologiya/" "/search/%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF/"; do
  curl -sS -o /dev/null $HOST "${BASE}${path}" || true
  curl -sS -o /dev/null $HOST "${BASE}${path}" || true
done

if [[ -x .local/regression-perf.sh ]]; then
  BASE_URL="${BASE_URL:-https://213.139.209.184}" HOST_HEADER="${HOST_HEADER:-almamed.su}" \
    ./.local/regression-perf.sh || true
fi

if [[ -x .local/check-sitemap.sh ]]; then
  BASE_URL="${BASE_URL:-https://213.139.209.184}" HOST_HEADER="${HOST_HEADER:-almamed.su}" \
    ./.local/check-sitemap.sh || true
fi

if command -v php >/dev/null 2>&1 && [[ -f .local/opcache-audit.php ]]; then
  run_as_owner php .local/opcache-audit.php || true
fi

if [[ -n "${THEME_VER:-}" ]]; then
  LIVE=$(curl -sS $HOST "${BASE}/" 2>/dev/null | grep -oE 'profitbuy\.min\.css\?v[0-9.]+' | head -1 || true)
  if [[ -z "$LIVE" && -n "${HOST_HEADER:-}" ]]; then
    LIVE=$(curl -sS --http1.1 -H "Host: ${HOST_HEADER}" "${BASE_URL:-https://almamed.su}/" 2>/dev/null \
      | grep -oE 'profitbuy\.min\.css\?v[0-9.]+' | head -1 || true)
  fi
  echo "live CSS URL: ${LIVE:-curl failed}"
  if [[ -n "$LIVE" && "$LIVE" != *"?v${THEME_VER}.${COMBINED_ED}"* && "$LIVE" != *"?v${THEME_VER}"* ]]; then
    echo "WARN: HTML still serves old ?v= — expected v${THEME_VER}.${COMBINED_ED}, got ${LIVE}"
  fi
fi

echo "=== done ==="
