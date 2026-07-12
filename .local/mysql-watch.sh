#!/usr/bin/env bash
# Фоновый watchdog: если MySQL упал — поднять снова (local dev).
set -uo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RUN_DIR="$ROOT/.local/run"
PHP72=/opt/homebrew/opt/php@7.2
WATCH_PID="$RUN_DIR/mysql-watch.pid"
LOG="$RUN_DIR/mysql-watch.log"
MYSQL_PIDFILE="$RUN_DIR/almamed-mysqld.pid"

mkdir -p "$RUN_DIR"
echo $$ >"$WATCH_PID"

php_db_ok() {
  "$PHP72/bin/php" -r "
    \$c = include '$ROOT/wa-config/db.php';
    \$d = \$c['default'];
    \$m = @new mysqli(\$d['host'] ?: 'localhost', \$d['user'], \$d['password'], \$d['database']);
    exit(\$m && !\$m->connect_error ? 0 : 1);
  " 2>/dev/null
}

mysqld_alive() {
  if [ -f "$MYSQL_PIDFILE" ]; then
    local pid
    pid="$(cat "$MYSQL_PIDFILE" 2>/dev/null || true)"
    [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null && return 0
  fi
  lsof -i:3306 -sTCP:LISTEN >/dev/null 2>&1
}

fail_count=0

while true; do
  if php_db_ok; then
    fail_count=0
  elif ! mysqld_alive; then
    fail_count=$((fail_count + 1))
    if [ "$fail_count" -ge 2 ]; then
      echo "$(date '+%F %T') MySQL down, restart…" >>"$LOG"
      rm -f /tmp/mysql.sock "$MYSQL_PIDFILE"
      "$ROOT/.local/start-mysql-dev.sh" >>"$LOG" 2>&1 || true
      fail_count=0
    fi
  else
    # mysqld слушает, но PHP не коннектится — не перезапускаем
    fail_count=0
  fi
  sleep 5
done
