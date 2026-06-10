<?php

class shopProductbrandsPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        /**
         * @var shopProductbrandsPlugin $plugin
         */
        $plugin = wa()->getPlugin('productbrands');

        $feature_model = new shopFeatureModel();
        $features = $feature_model->select('*')->where('selectable = 1')->order('id ASC')->limit(500)->fetchAll();
        $this->view->assign('features', $features);

        $key = array('shop', 'productbrands');

        $app_settings_model = new waAppSettingsModel();
        $feature_id = $app_settings_model->get($key, 'feature_id');
        if (!$feature_id) {
            $codes = array('brand', 'brend', 'manufacturer', 'make');
            foreach ($features as $f) {
                if (in_array($f['code'], $codes)) {
                    $feature_id = $f['id'];
                    break;
                }
            }
        }
        $this->view->assign('feature_id', $feature_id);
        $this->view->assign('settings', $plugin->getSettings());

        $this->view->assign('sizes', $app_settings_model->get($key, 'sizes'));
        $this->view->assign('categories_filter', $app_settings_model->get($key, 'categories_filter'));
        $this->view->assign('title_h1', $app_settings_model->get($key, 'title_h1'));
        $this->view->assign('sort', $app_settings_model->get($key, 'sort'));

        $path = wa()->getAppPath('plugins/productbrands/templates/', 'shop');

        if ($t_nav = $app_settings_model->get($key, 'template_nav')) {
            $this->view->assign('template_nav', $t_nav);
        } else {
            $this->view->assign('template_nav', file_get_contents($path.'frontendNav.html'));
        }

        if ($t_search = $app_settings_model->get($key, 'template_search')) {
            $this->view->assign('template_search', $t_search);
        } else {
            $this->view->assign('template_search', file_get_contents($path.'frontendSearch.html'));
        }

        if ($t_brands = $app_settings_model->get($key, 'template_brands')) {
            $this->view->assign('template_brands', $t_brands);
        } else {
            $this->view->assign('template_brands', file_get_contents($path.'frontendBrands.html'));
        }
    }
}
