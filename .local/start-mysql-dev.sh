#!/usr/bin/env bash
# Локальный MySQL 8.0 для PHP 7.2 (не MySQL 9.x).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RUN_DIR="$ROOT/.local/run"
MYSQL80=/opt/homebrew/opt/mysql@8.0
PHP72=/opt/homebrew/opt/php@7.2
CNF="$ROOT/.local/mysql/dev.cnf"
LOG="$RUN_DIR/mysql.log"
LOCK="$RUN_DIR/mysql-start.lockdir"
MYSQL_PIDFILE="$RUN_DIR/almamed-mysqld.pid"

mkdir -p "$RUN_DIR"
if ! mkdir "$LOCK" 2>/dev/null; then
  echo "MySQL start уже идёт — ждём…" >&2
  for _ in $(seq 1 60); do
    [ ! -d "$LOCK" ] && break
    sleep 1
  done
fi
trap 'rmdir "$LOCK" 2>/dev/null || true' EXIT

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

cleanup_stale() {
  if mysqld_alive; then
    return 0
  fi
  rm -f /tmp/mysql.sock "$MYSQL_PIDFILE"
}

if php_db_ok; then
  echo "MySQL OK (3306)"
  exit 0
fi

cleanup_stale

if mysqld_alive; then
  echo "mysqld жив, ждём подключение PHP…" >&2
  for i in $(seq 1 20); do
    sleep 1
    php_db_ok && { echo "MySQL OK за ${i}s"; exit 0; }
  done
  echo "mysqld жив, но PHP не подключается — grants/host?" >&2
  exit 1
fi

if [ ! -x "$MYSQL80/bin/mysqld" ]; then
  echo "Нет $MYSQL80 — brew install mysql@8.0" >&2
  exit 1
fi

echo "Запуск MySQL 8.0 (daemonize, light dev)…"
"$MYSQL80/bin/mysqld" --defaults-file="$CNF" --daemonize >>"$LOG" 2>&1

for i in $(seq 1 30); do
  sleep 1
  if php_db_ok; then
    echo "MySQL OK за ${i}s"
    exit 0
  fi
done

echo "MySQL не поднялся за 30s — tail $RUN_DIR/mysql.err" >&2
tail -20 "$RUN_DIR/mysql.err" >&2 || true
exit 1
