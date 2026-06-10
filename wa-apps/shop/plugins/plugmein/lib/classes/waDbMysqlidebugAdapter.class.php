<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/shopPlugmeinPluginMysqli.class.php';

/*From DEV waDbMysqlidebugAdapter*/
class waDbMysqlidebugAdapter extends waDbMysqliAdapter
{
    private $charset;

    public function connect($settings)
    {
        $host = $settings['host'];
        $port = isset($settings['port']) ? $settings['port'] : ini_get('mysqli.default_port');
        $handler = @new shopPlugmeinPluginMysqli($host, $settings['user'], $settings['password'], $settings['database'], $port);
        if ($handler->connect_error) {
            throw new waDbException($handler->connect_error, $handler->connect_errno);
        }

        $mysql_version = mysqli_get_server_info($handler);
        $mb4_is_supported = version_compare($mysql_version, self::MB4_SUPPORTED_VERSION, '>=');

        $this->charset = isset($settings['charset']) ? $settings['charset'] : 'utf8';
        if (!isset($settings['charset']) && $mb4_is_supported) {
            $this->charset = 'utf8mb4';
        }

        $charset_result = @$handler->set_charset($this->charset);
        if (!$charset_result) {
            $handler->set_charset('utf8'); // fallback
        }

        if (isset($settings['sql_mode'])) {
            $sql = "SET SESSION sql_mode = '".$handler->real_escape_string($settings['sql_mode'])."'";
            @$handler->query($sql);
        }
        return $handler;
    }
}