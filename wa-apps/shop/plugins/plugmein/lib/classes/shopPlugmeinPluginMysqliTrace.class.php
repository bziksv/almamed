<?php

require_once __DIR__ . '/../vendor/autoload.php';

class shopPlugmeinPluginMysqliTrace extends \Dzegarra\TracyMysqli\BarPanel implements \Tracy\IBarPanel
{
    /**
     * Title
     * @var string
     */
    protected static $title = 'MySQLi logger';

    /**
     * Get total queries execution time
     * @return string
     */
    protected function getTotalTime()
    {
        return ceil(array_sum(array_column($this->getQueries(), 'time')) * 1000);
    }

    /**
     * Renders HTML code for custom tab.
     * @return string
     */
    public function getTab()
    {
        $html = '<img src="'.self::$icon.'" alt="mysqli queries logger" /> ';
        $queries = $this->getQueries();
        $count = count($queries);
        if ($count == 0) {
            $html .= 'no queries!';
            return self::$title;
        } elseif ($count == 1) {
            $html .= '1 query';
        } else {
            $html .= $count . ' queries';
        }
        $html .= ' / '.$this->getTotalTime().' ms';
        return $html;
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     * @throws waException
     */
    public function getPanel()
    {
        $queries = $this->getQueries();
        $html = '<h1 '.self::$title_attributes.'>'.self::$title.'</h1>';
        $html .= '<div class="tracy-inner">';
        if (count($queries) > 0) {
            $html .= '<table style="width:400px;">';
            $html .= '<tr>';
            $html .= '<th>Time(ms)</td>';
            $html .= '<th>Statement</td>';
            $html .= '</tr>';
            foreach ($queries as $query) {
                if (!wa()->getPlugin('plugmein')->getSettings('long_queries') || $query['time'] > 0.001) {
                    $html .= '<tr>';
                    $html .= '<td><span '.self::$time_attributes.'>'.ceil($query['time'] * 1000).'</span></td>';
                    $html .= '<td '.self::$query_attributes.'>'.$query['statement'].'</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</table>';
        } else {
            $html .= '<p style="font-size:1.2em;font-weigt:bold;padding:10px">No queries were executed!</p>';
        }
        $html .= '</div>';

        return $html;
    }
}
