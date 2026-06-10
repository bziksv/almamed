<?php

class shopProductbrandsPluginBackendUrlController extends waJsonController
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('productbrands');
        $brands = $plugin->getBrands();

        $brands_model = new shopProductbrandsModel();
        foreach ($brands as $brand) {
            if (!$brand['url']) {
                $brand['url'] = shopHelper::transliterate($brand['name']);
                $brands_model->exec(
                    "INSERT INTO {$brands_model->getTableName()} (id, name, url) VALUES 
                    (i:id, s:name, s:url) ON DUPLICATE KEY UPDATE url = s:url", $brand);
                $this->response[] = array(
                    $brand['name'],
                    $brand['url']
                );
            }
        }
    }
}