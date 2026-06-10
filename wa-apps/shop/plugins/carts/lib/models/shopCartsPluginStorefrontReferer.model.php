<?php


class shopCartsPluginStorefrontRefererModel extends waModel
{

    protected $table = 'shop_carts_plugin_storefront_referer';

    public function saveReferer($storefront_id, $referer = null, $landing = null)
    {
        $e = $this->where('storefront_id=?', $storefront_id)
            ->order('id DESC')->limit('1')->fetchAssoc();

        if(!$e || ($e['referer'] !== $referer)) {
            $this->insert(array(
                'storefront_id' => $storefront_id,
                'referer' => $referer,
                'landing' => $landing,
                'create_datetime' => date('Y-m-d H:i:s')
            ));
        }
    }

}