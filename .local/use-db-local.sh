#!/usr/bin/env bash
# Вернуть local MySQL (127.0.0.1).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

cp wa-config/db.php.local wa-config/db.php

echo "→ wa-config/db.php = local (127.0.0.1 / almamed_su_db)"

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
