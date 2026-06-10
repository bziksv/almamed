#!/bin/sh
set -e
cd "$(dirname "$0")"
PROJECT="$(pwd)"
PHP72=/opt/homebrew/opt/php@7.2
NGINX=/opt/homebrew/bin/nginx
RUN_DIR="$PROJECT/.local/run"
OPCACHE_SO="$(find /opt/homebrew/Cellar/php@7.2 -path '*/opcache.so' 2>/dev/null | head -1)"

mkdir -p "$RUN_DIR"

# MySQL 8.0
if ! "$PHP72/bin/mysql" -u almamed -plocaldev -e "SELECT 1" almamed_su_db >/dev/null 2>&1; then
  if ! lsof -i:3306 -sTCP:LISTEN >/dev/null 2>&1; then
    "$PHP72/bin/mysqld_safe" --datadir=/opt/homebrew/var/mysql@8.0 --port=3306 >/dev/null 2>&1 &
    sleep 2
  fi
fi

# Stop built-in PHP server / old nginx on 8080
lsof -ti:8080 2>/dev/null | xargs kill -9 2>/dev/null || true
lsof -ti:9072 2>/dev/null | xargs kill -9 2>/dev/null || true
[ -f "$RUN_DIR/php-fpm.pid" ] && kill "$(cat "$RUN_DIR/php-fpm.pid")" 2>/dev/null || true
[ -f "$RUN_DIR/nginx.pid" ] && kill "$(cat "$RUN_DIR/nginx.pid")" 2>/dev/null || true
sleep 1

# Generate configs with absolute paths
USER_NAME="$(whoami)"
USER_GROUP="$(id -gn)"
sed "s|PROJECT_ROOT|$PROJECT|g; s|RUN_DIR|$RUN_DIR|g" "$PROJECT/.local/nginx/nginx.conf" \
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

echo "OK: http://localhost:8080/ → HTTP $HTTP ($(wc -c </tmp/almamed-check.html | tr -d ' ') bytes), категория → $CAT"
echo "nginx + php-fpm 7.2 (opcache), stop: ./stop-dev.sh"
