<?php
/**
 * Count SQL queries for product page render.
 */
$slug = $argv[1] ?? 'avtorefkeratometr-bez-stolika-bez-poverki-rmk-200-kitay';

$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/product/' . $slug . '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = '8080';

$GLOBALS['_profile'] = ['count' => 0, 'ms' => 0.0, 'slow' => []];

require dirname(__DIR__) . '/wa-system/autoload/waAutoload.class.php';
waAutoload::register();

require dirname(__DIR__) . '/wa-config/SystemConfig.class.php';
$config = new SystemConfig('wa-config');
waSystem::getInstance(null, $config);

class ProfileDbAdapter extends waDbMysqliAdapter
{
    public function query($query)
    {
        $t = microtime(true);
        $result = parent::query($query);
        $ms = (microtime(true) - $t) * 1000;
        $GLOBALS['_profile']['count']++;
        $GLOBALS['_profile']['ms'] += $ms;
        if ($ms > 100) {
            $GLOBALS['_profile']['slow'][] = [round($ms), substr(preg_replace('/\s+/', ' ', $query), 0, 120)];
        }
        return $result;
    }
}

// Replace adapter on default connection after init
$t0 = microtime(true);
ob_start();
try {
    wa()->dispatch();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$html = ob_get_clean();
$total = (microtime(true) - $t0) * 1000;

$p = $GLOBALS['_profile'];
echo "Product: $slug\n";
echo "Total: " . round($total) . " ms\n";
echo "HTML size: " . strlen($html) . " bytes\n";
echo "Queries: {$p['count']}\n";
echo "Query time: " . round($p['ms']) . " ms\n";
if ($p['slow']) {
    echo "Slow queries (>100ms):\n";
    foreach (array_slice($p['slow'], 0, 10) as $s) {
        echo "  {$s[0]}ms: {$s[1]}\n";
    }
}
