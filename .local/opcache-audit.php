#!/usr/bin/env php
<?php
/**
 * OPcache audit for prod/local (PHP 7.2+).
 * Usage: php .local/opcache-audit.php
 *        sudo -u almamed.su php .local/opcache-audit.php
 */
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$ok = true;
$lines = array();

function line($msg)
{
    global $lines;
    $lines[] = $msg;
    echo $msg . "\n";
}

line('=== OPcache audit ===');
line('php=' . PHP_VERSION . ' sapi=' . php_sapi_name());
line('opcache.enable=' . (ini_get('opcache.enable') ? '1' : '0'));
line('opcache.enable_cli=' . (ini_get('opcache.enable_cli') ? '1' : '0'));

$recommended = array(
    'opcache.memory_consumption' => '>= 128',
    'opcache.max_accelerated_files' => '>= 10000',
    'opcache.validate_timestamps' => '0 on prod (1 on dev)',
    'opcache.revalidate_freq' => '0 if validate_timestamps=0',
    'opcache.interned_strings_buffer' => '>= 16',
);

foreach ($recommended as $key => $hint) {
    $val = ini_get($key);
    line(sprintf('%s=%s  (%s)', $key, $val === false || $val === '' ? '(not set)' : $val, $hint));
}

if (!function_exists('opcache_get_status')) {
    line('FAIL: opcache_get_status() unavailable');
    exit(1);
}

$status = opcache_get_status(false);
if (!$status || empty($status['opcache_enabled'])) {
    if (php_sapi_name() === 'cli' && !ini_get('opcache.enable_cli')) {
        line('');
        line('SKIP: opcache.enable_cli=0 — на prod проверять через php-fpm/Apache (не CLI).');
        line('Hint: curl https://almamed.su/... или php-fpm -i | grep opcache');
        exit(0);
    }
    line('FAIL: OPcache disabled or status unavailable');
    exit(1);
}

$mem = ifset($status, 'memory_usage', array());
$stats = ifset($status, 'opcache_statistics', array());
$hits = (int) ifset($stats, 'hits', 0);
$misses = (int) ifset($stats, 'misses', 0);
$total = $hits + $misses;
$hit_rate = $total > 0 ? round(100 * $hits / $total, 2) : 0;

line('');
line('memory_used_mb=' . round(ifset($mem, 'used_memory', 0) / 1048576, 1));
line('memory_free_mb=' . round(ifset($mem, 'free_memory', 0) / 1048576, 1));
line('memory_wasted_mb=' . round(ifset($mem, 'wasted_memory', 0) / 1048576, 2));
line('wasted_pct=' . round(ifset($mem, 'current_wasted_percentage', 0), 2));
line('cached_scripts=' . ifset($stats, 'num_cached_scripts', '?'));
line('hit_rate_pct=' . $hit_rate . ' (hits=' . $hits . ' misses=' . $misses . ')');
line('oom_restarts=' . ifset($stats, 'oom_restarts', 0));
line('hash_restarts=' . ifset($stats, 'hash_restarts', 0));

if ((int) ifset($mem, 'used_memory', 0) < 64 * 1048576) {
    line('WARN: used memory < 64 MB — consider opcache.memory_consumption=256 on prod');
    $ok = false;
}
if ($hit_rate > 0 && $hit_rate < 90 && $total > 1000) {
    line('WARN: hit rate < 90% after warm traffic');
    $ok = false;
}
if ((float) ifset($mem, 'current_wasted_percentage', 0) > 10) {
    line('WARN: wasted > 10% — restart php-fpm or opcache_reset after deploy');
    $ok = false;
}
if ((int) ifset($stats, 'oom_restarts', 0) > 0) {
    line('WARN: oom_restarts > 0 — increase opcache.memory_consumption');
    $ok = false;
}

line('');
line($ok ? 'RESULT: OK' : 'RESULT: review warnings above');
exit($ok ? 0 : 2);
