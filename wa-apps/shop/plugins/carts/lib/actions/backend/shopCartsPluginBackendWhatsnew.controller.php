<?php


class shopCartsPluginBackendWhatsnewController extends waController {


    public function execute($params = null)
    {

        /**
         * @var shopCartsPlugin $plugin
         */
        $plugin = wa('shop')->getPlugin('carts');

        $whatsnew = $plugin->getPath().'/lib/config/whatsnew.php';
        if(file_exists($whatsnew)) {

            $whatsnew = include $whatsnew;

            if(waRequest::post('know')) {
                end($whatsnew);
                $key = key($whatsnew);
                $plugin->saveSettings(array('whatsnew' => $key));
            }

            $key = $plugin->getSettings('whatsnew');
            $html = '<h1>'._wp('What\'s new?').'</h1><div class="highlighted block"><ol>';
            foreach($whatsnew as $k => $s) {
                if($key < $k)
                    $html .= '<li>'.$s.'</li>';
            }
            $html .= '</ol></div>';
            $html .= '<div class="block hint"><p>'.
                sprintf(
                    _wp('<a href="%s">Contact us</a> for support.'),
                    _wp('https://hardmandev.com/feedback/')
                ). ' '.
                sprintf(
                    _wp('Don\'t forget to leave a <a href="%s">review</a>.'),
                    _wp('http://www.webasyst.com/store/plugin/shop/carts/reviews/')
                ).'</p></div>';

            echo $html;
        }
    }
}
