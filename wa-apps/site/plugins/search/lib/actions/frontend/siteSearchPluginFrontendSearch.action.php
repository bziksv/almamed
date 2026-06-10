<?php

class siteSearchPluginFrontendSearchAction extends waViewAction {

    public function execute()
    {
        $this->setLayout(new siteFrontendLayout());

        $on_page = 10;
        $page = waRequest::get('page', 1, waRequest::TYPE_INT);
        if($page < 1) $page = 1;
        $query = waRequest::get('query', '', waRequest::TYPE_STRING_TRIM);

        $search = new siteSearchPluginIndex();
        $domain_id = $this->getDomainId();

        $data = $search->search($query, ($page -1) * $on_page, $on_page, $domain_id);

        $pages = array();
        foreach ($data['pages'] as $p) {
            $p['frontend_url'] = wa()->getUrl() . rtrim($p['route'], '*').$p['full_url'];
            $pages[] = $p;
        }
        $this->view->assign('pages', $pages);
        $this->view->assign('paging', array(
            'total' => ceil($data['total']/ $on_page),
            'page' => $page,
            'on_page' => $on_page
        ));

        if($this->getTheme()->getFile('search.plugin.html'))
            $this->setThemeTemplate('search.plugin.html');
    }

    protected function getDomainId()
    {
        $domains = siteHelper::getDomains(true);
        $current_domain = wa()->getRouting()->getDomain();

        foreach ($domains as $id => $domain) {
            if($domain['name'] == $current_domain) {
                return $id;
            }
        }

        reset($domains);
        return key($domains);
    }
}