<?php

require_once __DIR__ . '/../vendor/autoload.php';

class shopPlugmeinPluginMysqli extends \Dzegarra\TracyMysqli\Mysqli
{
    const LONG_QUERY_TIME = 0.001;

    /**
     * @see \mysqli::query
     */
    public function query($query, $resultmode = MYSQLI_STORE_RESULT)
    {
        $start = microtime(true);
        $result = parent::query($query, $resultmode);
        $time = microtime(true) - $start;
        $this->addLog($query, $time);
        $this->addSlowLog($query, $time);
        return $result;
    }

    public function addSlowLog($query, $time)
    {
        if ($time > self::LONG_QUERY_TIME) {
            $classes = [];
            foreach (debug_backtrace(0, 15) as $call) {
                if (empty($call['class'])
                    || stripos($call['class'], 'mysql')
                    || stripos($call['class'], 'model')) {
                    continue;
                }
                if ($call['class'] == 'Smarty_Internal_TemplateBase' && isset($call['args'][0]) && is_string($call['args'][0])) {
                    $classes[] = $call['class'] . " ({$call['args'][0]})";
                } else {
                    $classes[] = $call['class'];
                }
            }
            $debug = implode(' -> ', $classes);
            \waLog::log(round($time, 2) . "s - $debug \n$query", 'mysql-slow.log');
        }
    }


}
