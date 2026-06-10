<?php

class shopProductbrandsPluginPagesEditAction extends waViewAction
{
    public function execute()
    {
        $page = array();
        $url = '';

        $routes = wa()->getRouting()->getByApp('shop');
        foreach ($routes as $d => $domain_routes) {
            foreach ($domain_routes as $route) {
                $url = 'http://'.$d.'/'.wa()->getRouting()->clearUrl($route['url']).'/brand/'.$url;
                break 2;
            }
        }

        if ($url) {
            $idna = new waIdna();
            $url_decoded = $idna->decode($url);
        } else {
            $url_decoded = null;
        }

        $data = array(
            'url'          => $url,
            'url_decoded'  => $url_decoded,
            'page'         => $page,
            'page_url'     => '#/',
            'options'      => array(
                'container' => true,
                'show_url' => false,
                'save_panel' => true,
                'js' => array(
                    'ace' => true,
                    'editor' => true,
                    'storage' => false,
                ),
                'is_ajax' => true,
                'data' => array()
            ),
            'lang'         => substr(wa()->getLocale(), 0, 2),
            'ibutton'      => true,
            'upload_url'   => wa('shop')->getDataUrl('img', true)
        );
        $this->view->assign($data);
        $template = $this->getConfig()->getRootPath().'/wa-system/page/templates/PageEdit.html';
        $this->setTemplate($template);
    }
}