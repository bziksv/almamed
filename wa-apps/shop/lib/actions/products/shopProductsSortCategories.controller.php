<?php

class shopProductsSortCategoriesController extends waController
{
    public function execute()
    {
        if (waRequest::post()) {
            $model = new shopCategoryModel();
            $before = $model->select('id, parent_id, name, sort')->order('parent_id, sort, id')->fetchAll('id');
            $model->sortTree(true);
            if ($plugin = shopUserlogPlugin::getInstance()) {
                $after = $model->select('id, parent_id, name, sort')->order('parent_id, sort, id')->fetchAll('id');
                $plugin->logCategorySort($before, $after);
            }
        }
        $this->getResponse()->redirect('?action=products');
    }
}
