<?php

class shopProductbrandsPluginFrontendBrandsAction extends shopFrontendAction
{
    public function execute()
    {
        if ($t = wa()->getSetting('template_brands', '', array('shop', 'productbrands'))) {
            $template = 'string:'.$t;
        } else {
            $template = 'file:'.wa()->getAppPath('plugins/productbrands/templates/', 'shop').'frontendBrands.html';
        }

        $brands = shopProductbrandsPlugin::getBrands();
        $this->view->assign('brands', $brands);

        $plugin = wa('shop')->getPlugin('productbrands');

        $title = $plugin->getSettings('brands_name');
        if (!$title) {
            $title = _w('Brands');
        }

        $this->setThemeTemplate('page.html');

        $this->view->assign('page', array(
            'id' => 'brands',
            'title' => $title,
            'name' => $title,
            'content' => $this->view->fetch($template)
        ));

        $this->getResponse()->setTitle($title);

        if ($tmp = $plugin->getSettings('brands_meta_description')) {
            $this->getResponse()->setMeta('description', $tmp);
        }
        if ($tmp = $plugin->getSettings('brands_meta_keywords')) {
            $this->getResponse()->setMeta('keywords', $tmp);
        }

        waSystem::popActivePlugin();
    }
}