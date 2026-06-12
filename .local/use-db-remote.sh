#!/usr/bin/env bash
# Подключить local dev к боевой MySQL (45.90.35.63).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

cp wa-config/db.php wa-config/db.php.local.bak 2>/dev/null || true
cp wa-config/db.php.remote wa-config/db.php

echo "→ wa-config/db.php = remote (45.90.35.63 / almamed_su_db)"
echo "  Проверка подключения..."

/opt/homebrew/opt/php@7.2/bin/php -r "
\$c = include '$ROOT/wa-config/db.php';
\$d = \$c['default'];
\$m = new mysqli(\$d['host'], \$d['user'], \$d['password'], \$d['database']);
if (\$m->connect_error) { fwrite(STDERR, \$m->connect_error . PHP_EOL); exit(1); }
\$r = \$m->query('SELECT COUNT(*) c FROM shop_product');
echo '  OK, shop_product=' . \$r->fetch_assoc()['c'] . PHP_EOL;
"

php cli.php webasyst clearCache >/dev/null 2>&1 || true
echo "  cache cleared"
echo ""
echo "⚠️  Local dev пишет в БОЕВУЮ базу. Главная может грузиться 5–30+ с."
echo "    Вернуть local: ./.local/use-db-local.sh"
