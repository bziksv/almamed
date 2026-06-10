<?php

class siteSearchPluginCli extends waCliController
{
    public function execute()
    {
        $search = new siteSearchPluginIndex();
        if (waRequest::param(0)) {
            $search->indexPage(waRequest::param(0));
        } else {
            @set_time_limit(0);
            $page_model = new sitePageModel();
            $index_model = new siteSearchPluginIndexModel();
            $n = $page_model->countAll();
            $limit = 100;
            $i = 0;

            $index_model->truncate();
            while ($i < $n) {
                $pages = $page_model->select('*')->limit($i.", ".$limit)->fetchAll('id');
                foreach ($pages as $p) {
                    $search->indexPage($p, false);
                }
                $i += $limit;
            }
        }
    }
}