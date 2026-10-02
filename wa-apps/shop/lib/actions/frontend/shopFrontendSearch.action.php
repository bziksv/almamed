<?php

class shopFrontendSearchAction extends shopFrontendAction
{
    public function execute()
    {
        require_once wa()->getAppPath('lib/classes/shopSearchGate.class.php', 'shop');
        shopSearchGate::run();

        $query = waRequest::get('query');
        $this->setCollection(new shopProductsCollection('search/query='.str_replace('&', '\&', $query)));

        $query = htmlspecialchars($query);
        $this->view->assign('title', $query);
        $this->getResponse()->setTitle($query.' — '.$this->getStoreName());

        if ($this->layout) {
            $this->layout->assign('query', $query);
        }
        if (!$query) {
            $this->view->assign('sorting', true);
        }

        /**
         * @event frontend_search
         * @return array[string]string $return[%plugin_id%] html output for search
         */
        $this->view->assign('frontend_search', wa()->event('frontend_search'));
        $this->setThemeTemplate('search.html');
    }
}