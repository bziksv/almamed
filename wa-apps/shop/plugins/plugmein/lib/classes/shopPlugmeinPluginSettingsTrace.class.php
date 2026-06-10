<?php

require_once __DIR__ . '/../vendor/autoload.php';

class shopPlugmeinPluginSettingsTrace implements \Tracy\IBarPanel
{
    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @link http://www.flaticon.com/free-icon/database_51319
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    protected static $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAPCAQAAACR313BAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADdcAAA3XAUIom3gAAAAHdElNRQfjAwwAEwlJQf/SAAAAv0lEQVQY06XOPUuCcRSG8V/1RJA8g4GI9A2aG91bWhoiGiQSXN110W/QWLg1F9TUJDiILYGLiwTS7CCOgmD/hsfA15aus9yci5tzdmR09S1zouQticcerfJhPwkRYlnf4gX9Ka/1qy/MjJWW+t1E7/qT/+qwcb+3vT31rOJFvEnfuXLp1pfqup7oeVfV0XPtgJzgSUMQBG0UBEW0ZaOVdl7Zg5GmsplhtHb7zJG6mlP3ye9pKYfS87nRMfDqHH4Al3ArM5Pn2UcAAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMDMtMTFUMjM6MTk6MDkrMDE6MDASkb+rAAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTAzLTExVDIzOjE5OjA5KzAxOjAwY8wHFwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAAASUVORK5CYII=';
    /**
     * Title
     * @var string
     */
    protected static $title = 'Settings';

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


    /**
     * Get total queries execution time
     * @return string
     */
    protected function getTotalTime()
    {
        $start = wa()->getView()->smarty->start_time;
        return round((microtime(true) - $start) * 1000);
    }

    /**
     * Renders HTML code for custom tab.
     * @return string
     */
    public function getTab()
    {
        $html = '<img src="'.self::$icon.'" alt="smarty logger" /> ';
        $html .= 'App Settings: ' . wa()->getConfig()->getName();
        return $html;
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    public function getPanel()
    {
        $html = '<div class="tracy-inner" style="width: 1000px">';

        $html .= '<h2><a class="tracy-toggle tracy-collapsed" data-tracy-ref="^div .tracy-settings-routing">Routing rules</a></h2>';
        $html .= '<div class="tracy-settings-routing tracy-collapsed">';
        $routes = wa()->getConfig()->getRouting();
        $html .= '<pre>'.wa_dump_helper($routes).'</pre>';
        $html .= '</div>';

        $html .= '<h2><a class="tracy-toggle tracy-collapsed" data-tracy-ref="^div .tracy-settings-classes">Classes</a></h2>';
        $html .= '<div class="tracy-settings-classes tracy-collapsed">';
        $classes = wa()->getConfig()->getClasses();
        $html .= '<pre>'.wa_dump_helper($classes).'</pre>';
        $html .= '</div>';

        $html .= '<h2><a class="tracy-toggle tracy-collapsed" data-tracy-ref="^div .tracy-settings-plugins">Plugins</a></h2>';
        $html .= '<div class="tracy-settings-plugins tracy-collapsed">';
        $plugins = wa()->getConfig()->getPlugins();
        $html .= '<pre>'.wa_dump_helper($plugins).'</pre>';
        $html .= '</div>';

        $html .= '<h2><a class="tracy-toggle tracy-collapsed" data-tracy-ref="^div .tracy-settings-options">Options</a></h2>';
        $html .= '<div class="tracy-settings-options tracy-collapsed">';
        $options = wa()->getConfig()->getOption();
        $html .= '<pre>'.wa_dump_helper($options).'</pre>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }
}
