<?php

require_once __DIR__ . '/../vendor/autoload.php';

class shopPlugmeinPluginEventTrace implements \Tracy\IBarPanel
{
    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @link http://www.flaticon.com/free-icon/database_51319
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    protected static $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAPCAQAAACR313BAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADdcAAA3XAUIom3gAAAAHdElNRQfjAwsOMxlWO96nAAAAnUlEQVQY033RMRKCMBCF4W88AAzW9gIz5KaKJ1NrrqDQYB8LY0ALkubN/3bydrMsZ28STfYL2oHgrHRQoXJQOgvfkuApGsyiKJoNoofuY18S/r/9xy4NGb2yGhRQadOzV0fUbimiVTHl+jr10mQyyXJejThnasyySWabybjOvmnQuq+zKbY6p9+eO3hs/Rqdk0JIdlA4Lebvxsb1xt67hH68EDCt6wAAACV0RVh0ZGF0ZTpjcmVhdGUAMjAxOS0wMy0xMVQxMzo1MToyNSswMTowMEh7xA8AAAAldEVYdGRhdGU6bW9kaWZ5ADIwMTktMDMtMTFUMTM6NTE6MjUrMDE6MDA5JnyzAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAABJRU5ErkJggg==';
    /**
     * Title
     * @var string
     */
    protected static $title = 'Events';

    /**
     * Title HTML attributes
     * @var string
     */
    protected static $title_attributes = 'style="font-size:1.6em"';

    /**
     * Time table cell HTML attributes
     * @var string
     */
    protected static $time_attributes = 'style="font-weight:bold;color:#333;font-family:Courier New;font-size:1.1em"';

    /**
     * Query table cell HTML attributes
     * @var string
     */
    protected static $query_attributes = '';

    /**
     * mysqli logged queries
     * @var array[]
     */
    protected $queries;

    public function getEvents()
    {
        static $events;

        if ($events) {
            return $events;
        }

        $file = waConfig::get('wa_path_log') . '/webasyst/waEventExecutionTime.log';
        $backup = waConfig::get('wa_path_log') . '/webasyst/waEventExecutionTime.all.log';
        if (file_exists($file)) {
            $log = file_get_contents($file);

            $re = '/\'class\' => \'(?<class>\w*)\'.*?0 => \'(?<method>\w*).*?\'execution_time\' => (?<time>[0-9\.]*)/s';
            preg_match_all($re, $log, $matches, PREG_SET_ORDER, 0);

            foreach ($matches as $m) {
                if (!wa()->getPlugin('plugmein')->getSettings('long_events') || $m['time'] > 0.001) {
                    $events[] = ['class' => $m['class'], 'method' => $m['method'], 'time' => $m['time']];
                }
            }
            file_put_contents($backup, $log, FILE_APPEND);
            waFiles::delete($file);
            return $events;
        }
    }

    /**
     * Get total queries execution time
     * @return string
     */
    protected function getTotalTime()
    {
        $time = 0;
        foreach ($this->getEvents() as $event) {
            $time += $event['time'];
        }
        return ceil($time * 1000);
    }

    /**
     * Renders HTML code for custom tab.
     * @return string
     */
    public function getTab()
    {
        $html = '<img src="'.self::$icon.'" alt="events logger" /> ';
        $events = $this->getEvents();
        $count = count($events);
        if ($count == 0) {
            $html .= 'no events!';
            return self::$title;
        } elseif ($count == 1) {
            $html .= '1 event';
        } else {
            $html .= $count . ' events';
        }
        $html .= ' / '.$this->getTotalTime().' ms';

        return $html;
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    public function getPanel()
    {
        $events = $this->getEvents();
        $html = '<h1 '.self::$title_attributes.'>'.self::$title.'</h1>';
        $html .= '<div class="tracy-inner">';
        if (count($events) > 0) {
            $html .= '<table style="width:400px;">';
            $html .= '<tr>';
            $html .= '<th>Time(ms)</td>';
            $html .= '<th>Method</td>';
            $html .= '</tr>';
            foreach ($events as $event) {
                $html .= '<tr>';
                $html .= '<td><span '.self::$time_attributes.'>'.round($event['time'] * 1000).'</span></td>';

                $html .= '<td '.self::$query_attributes.'>'.$event['class'].'::'.$event['method'].'</td>';

                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p style="font-size:1.2em;font-weigt:bold;padding:10px">No events were executed!</p>';
        }
        $html .= '</div>';

        return $html;
    }
}
