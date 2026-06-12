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

echo "=== done ==="
