<?php
/**
 * One-shot: create shop_leads_plugin_lead if missing.
 * Usage: php wa-apps/shop/plugins/leads/lib/config/install_table.php
 */
$path = dirname(__FILE__);
while ($path && !file_exists($path . '/wa-config/SystemConfig.class.php')) {
    $parent = dirname($path);
    if ($parent === $path) {
        fwrite(STDERR, "Cannot find Webasyst root\n");
        exit(1);
    }
    $path = $parent;
}
chdir($path);
require_once $path . '/wa-config/SystemConfig.class.php';
$config = new SystemConfig();
waSystem::getInstance(null, $config);
wa('shop');

$db = include dirname(__FILE__) . '/db.php';
$model = new waModel();

foreach ($db as $table => $fields) {
    try {
        $model->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
        echo "OK exists: {$table}\n";
        continue;
    } catch (Exception $e) {
        // create
    }

    $cols = array();
    $keys = array();
    foreach ($fields as $name => $def) {
        if ($name === ':keys') {
            $keys = $def;
            continue;
        }
        $type = $def[0];
        $size = isset($def[1]) && !is_array($def[1]) && !isset($def['null']) ? $def[1] : (isset($def[1]) && is_numeric($def[1]) ? $def[1] : null);
        // Webasyst format: array('varchar', 255, 'null' => 0, ...)
        $sql_type = strtoupper($type);
        if (isset($def[1]) && (is_int($def[1]) || (is_string($def[1]) && ctype_digit($def[1])))) {
            $sql_type .= '(' . $def[1] . ')';
        } elseif (isset($def[1]) && is_string($def[1]) && strpos($def[1], ',') !== false) {
            $sql_type .= '(' . $def[1] . ')';
        }
        if (!empty($def['unsigned'])) {
            $sql_type .= ' UNSIGNED';
        }
        $null = array_key_exists('null', $def) ? (bool) $def['null'] : true;
        $sql_type .= $null ? ' NULL' : ' NOT NULL';
        if (array_key_exists('default', $def)) {
            $d = $def['default'];
            if ($d === null) {
                $sql_type .= ' DEFAULT NULL';
            } else {
                $sql_type .= " DEFAULT '" . $model->escape($d) . "'";
            }
        }
        if (!empty($def['autoincrement'])) {
            $sql_type .= ' AUTO_INCREMENT';
        }
        $cols[] = '`' . $name . '` ' . $sql_type;
    }

    foreach ($keys as $kname => $kdef) {
        if ($kname === 'PRIMARY') {
            $cols[] = 'PRIMARY KEY (`' . (is_array($kdef) ? implode('`,`', $kdef) : $kdef) . '`)';
        } else {
            $parts = is_array($kdef) ? $kdef : array($kdef);
            $cols[] = 'KEY `' . $kname . '` (`' . implode('`,`', $parts) . '`)';
        }
    }

    $sql = 'CREATE TABLE IF NOT EXISTS `' . $table . '` (' . implode(', ', $cols) . ') ENGINE=MyISAM DEFAULT CHARSET=utf8';
    echo $sql . "\n";
    $model->exec($sql);
    echo "CREATED: {$table}\n";
}

echo "done\n";
