<?php

require_once __DIR__ . '/../vendor/autoload.php';

class shopPlugmeinPluginSmartyTrace implements \Tracy\IBarPanel
{
    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @link http://www.flaticon.com/free-icon/database_51319
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    protected static $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfjAwsPAxDxYzrHAAAAz0lEQVQoz4XPMU4CQRiG4SerW2iN8QBGYggJWNJpo4UewBuY5RoUHkBPwBWWAxAbYmjRYKEdRyAESBgb3dlFjO808828838ZfkhlRubmRjKpLY6MrfRlMn0rY7WqkPtQL1Ldp7x8fSq4AmdyD/ZcC06icCs4KNK9C4eCmyi0Bc1KZVPQijH1ZiApcmLg1X75RcdSr0g9S53tj3attcG5ta5fJCYewZNJrIs9G+/uHOPSs40dzITvNSsP/oey8LJjVxGGFmBh+Ne8hqmpRvnoC2Q8NWoALGRbAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDE5LTAzLTExVDE0OjAzOjE2KzAxOjAw/82jVgAAACV0RVh0ZGF0ZTptb2RpZnkAMjAxOS0wMy0xMVQxNDowMzoxNiswMTowMI6QG+oAAAAZdEVYdFNvZnR3YXJlAHd3dy5pbmtzY2FwZS5vcmeb7jwaAAAAAElFTkSuQmCC';
    /**
     * Title
     * @var string
     */
    protected static $title = 'Smarty';

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
        $html .= 'Smarty: '.$this->getTotalTime().' ms';
        return $html;
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    public function getPanel()
    {
        if (empty(shopPlugmeinPlugin::$templates)) {
            return '';
        }
        $html = '<div class="tracy-inner">';
        $html .= '<table style="width:400px;">';
        $html .= '<tr>';
        $html .= '<th>Template</td>';
        $html .= '</tr>';
        foreach (shopPlugmeinPlugin::$templates as $template) {
            $html .= '<tr>';
            $html .= '<td><span>'.$template.'</span></td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }
}
