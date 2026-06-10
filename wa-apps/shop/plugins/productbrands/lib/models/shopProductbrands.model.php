<?php

/**
 * Class shopProductbrandsModel
 *
 * @author wa-apps.ru <info@wa-apps.ru>
 * @copyright 2013-2016 wa-apps.ru
 * @license Webasyst License http://www.webasyst.ru/terms/#eula
 * @link http://www.webasyst.ru/store/plugin/shop/productbrands/
 */
class shopProductbrandsModel extends waModel
{
    protected $table = 'shop_productbrands';

    /**
     * @param int $id
     * @return array
     */
    public function getBrand($id)
    {
        $feature_values_model = new shopFeatureValuesVarcharModel();
        $feature = $feature_values_model->getById($id);

        $brand = $this->getById($id);
        if ($brand) {
            $brand['name'] = $feature['value'];
            $brand['params'] = self::getParams($brand['params']);
        } else {
            $brand = array(
                'id' => $id,
                'name' => $feature['value'],
                'summary' => '',
                'description' => '',
                'image' => null,
                'title' => '',
                'h1' => '',
                'meta_keywords' => '',
                'meta_description' => '',
                'url' => null,
                'filter' => '',
                'hidden' => 0,
                'sort_products' => '',
                'enable_sorting' => 0,
                'params' => array(),
            );
        }
        return $brand;
    }

    /**
     * @param string $params
     * @return array
     */
    public static function getParams($params)
    {
        if (!$params) {
            return array();
        }
        $lines = explode("\n", $params);
        $params = array();
        foreach ($lines as $l) {
            $parts = explode('=', trim($l), 2);
            if (count($parts) < 2) {
                continue;
            }
            $params[$parts[0]] = $parts[1];
        }
        return $params;
    }
}