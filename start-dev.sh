#!/bin/sh
set -e
cd "$(dirname "$0")"
PROJECT="$(pwd)"
PHP72=/opt/homebrew/opt/php@7.2
NGINX=/opt/homebrew/bin/nginx
RUN_DIR="$PROJECT/.local/run"
OPCACHE_SO="$(find /opt/homebrew/Cellar/php@7.2 -path '*/opcache.so' 2>/dev/null | head -1)"

mkdir -p "$RUN_DIR"

# MySQL 8.0 (PHP 7.2 не работает с MySQL 9.x)
"$PROJECT/.local/start-mysql-dev.sh" || exit 1

# Stop built-in PHP server / old nginx on 8080
lsof -ti:8080 2>/dev/null | xargs kill -9 2>/dev/null || true
lsof -ti:9072 2>/dev/null | xargs kill -9 2>/dev/null || true
[ -f "$RUN_DIR/php-fpm.pid" ] && kill "$(cat "$RUN_DIR/php-fpm.pid")" 2>/dev/null || true
[ -f "$RUN_DIR/nginx.pid" ] && kill "$(cat "$RUN_DIR/nginx.pid")" 2>/dev/null || true
sleep 1

# Generate configs with absolute paths
USER_NAME="$(whoami)"
USER_GROUP="$(id -gn)"
PROD_MEDIA_HOST="${PROD_MEDIA_HOST:-almamed.su}"
if [ -f "$PROJECT/.local/sync-prod.env" ]; then
  # shellcheck disable=SC1090
  . "$PROJECT/.local/sync-prod.env"
  PROD_MEDIA_HOST="${PROD_MEDIA_HOST:-almamed.su}"
fi
sed "s|PROJECT_ROOT|$PROJECT|g; s|RUN_DIR|$RUN_DIR|g; s|PROD_MEDIA_HOST|$PROD_MEDIA_HOST|g" "$PROJECT/.local/nginx/nginx.conf" \
  > "$RUN_DIR/nginx.conf"
sed "s|RUN_DIR|$RUN_DIR|g; s|POOL_CONF|$RUN_DIR/pool.conf|g" "$PROJECT/.local/php/fpm.conf" \
  > "$RUN_DIR/fpm.conf"
sed "s|USER_NAME|$USER_NAME|g; s|USER_GROUP|$USER_GROUP|g" "$PROJECT/.local/php/pool.conf" \
  > "$RUN_DIR/pool.conf"
sed "s|OPCACHE_SO|$OPCACHE_SO|g" "$PROJECT/.local/php/php.ini" \
  > "$RUN_DIR/php.ini"

export PHPRC="$RUN_DIR/php.ini"

# PHP-FPM
"$PHP72/sbin/php-fpm" -y "$RUN_DIR/fpm.conf" &
FPM_PID=$!
sleep 1

if ! kill -0 "$FPM_PID" 2>/dev/null; then
  echo "php-fpm failed — see $RUN_DIR/php-fpm.log"
  exit 1
fi

# Nginx
"$NGINX" -c "$RUN_DIR/nginx.conf"

sleep 1
HTTP=$(curl -sS -o /tmp/almamed-check.html -w '%{http_code}' --max-time 30 http://localhost:8080/ || echo 000)
SIZE=$(wc -c </tmp/almamed-check.html 2>/dev/null || echo 0)
CAT=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 30 -L --max-redirs 3 http://localhost:8080/category/anesteziologiya/ 2>/dev/null || echo 000)

if [ "$HTTP" != "200" ] || [ "$SIZE" -lt 100000 ]; then
  echo "FAIL: главная HTTP $HTTP, size $SIZE — см. $RUN_DIR/nginx-error.log и wa-log/error.log"
  exit 1
fi

if [ "$CAT" != "200" ]; then
  echo "FAIL: категория HTTP $CAT (ожидали 200) — nginx PATH_INFO, см. $RUN_DIR/nginx-error.log"
  exit 1
fi

# Media proxy (light dev): локального файла нет → nginx тянет с prod
if [ -f "$PROJECT/.local/run/media-proxy-test.path" ]; then
  TEST_PATH="$(tr -d '\n' < "$PROJECT/.local/run/media-proxy-test.path")"
  if [ -n "$TEST_PATH" ]; then
    PROXY=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 25 "http://localhost:8080$TEST_PATH" 2>/dev/null || echo 000)
    if [ "$PROXY" = "200" ]; then
      echo "OK: media proxy $TEST_PATH → HTTP $PROXY (prod: $PROD_MEDIA_HOST)"
    else
      echo "WARN: media proxy $TEST_PATH → HTTP $PROXY (нет сети или файл недоступен на prod)"
    fi
  fi
elif [ ! -d "$PROJECT/wa-data/public/shop/products" ] || [ -z "$(ls -A "$PROJECT/wa-data/public/shop/products" 2>/dev/null)" ]; then
  echo "TIP: ./.local/setup-light-dev.sh — удалить тяжёлые wa-data и включить proxy картинок с prod"
fi

echo "OK: http://localhost:8080/ → HTTP $HTTP ($(wc -c </tmp/almamed-check.html | tr -d ' ') bytes), категория → $CAT"
echo "nginx + php-fpm 7.2 (opcache), stop: ./stop-dev.sh"

# Watchdog: если MySQL упал — поднимет за ~3s
chmod +x "$PROJECT/.local/mysql-watch.sh" 2>/dev/null || true
[ -f "$RUN_DIR/mysql-watch.pid" ] && kill "$(cat "$RUN_DIR/mysql-watch.pid")" 2>/dev/null || true
nohup "$PROJECT/.local/mysql-watch.sh" >>"$RUN_DIR/mysql-watch.log" 2>&1 &
echo "mysql-watch: ON (лог $RUN_DIR/mysql-watch.log)"
