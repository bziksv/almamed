<?php


class shopCartsPluginOrderGetDataController extends waJsonController
{

    public function execute()
    {
        if($code = waRequest::get('code')) {
            $currency = waRequest::get('currency');
            $this->response['products'] = $this->getProducts($code, $currency);
            $this->response['contact'] = $this->getContact($code);
        } else {
            $this->setError(_wp('Unknown cart'));
        }

    }

    /**
     * @param $code
     * @param $currency
     * @return array
     */
    protected function getProducts($code, $currency)
    {
        $cart_products = new shopCartsPluginCartProducts();
        $items = $cart_products->getForBackend($code);

        $items = $items['items'];

        $products = array();

        foreach ($items as $item) {

            $product = $item['product'];
            $product['skus'] = $item['skus'];

            $sku_ids = array_keys($item['skus']);
            $sku_stocks = $this->getSkuStocks($sku_ids);


            foreach($product['skus'] as &$sku) {
                $sku['price_str'] = shop_currency($sku['price'], $item['currency'], $currency);
                $sku['price_html'] = shop_currency_html($sku['price'],  $item['currency'], $currency);
                $sku['price'] = shop_currency($sku['price'], $item['currency'], $currency, false);
                $this->workupSkuStocks($sku, $sku_stocks);
            }
            unset($sku);

            $product['services'] = $item['services'];
            if($product['services']) {
                foreach ($product['services'] as &$s) {
                    $s['price_str'] = shop_currency($s['price'], $s['currency'], $currency);
                    $s['price_html'] = shop_currency_html($s['price'],  $s['currency'], $currency);
                    $s['price'] = shop_currency_html($s['price'],  $s['currency'], $currency, false);
                    foreach ($s['variants'] as &$v) {
                        $v['price_str'] = shop_currency($v['price'], $s['currency'], $currency);
                        $v['price_html'] = shop_currency_html($v['price'],  $s['currency'], $currency);
                        $v['price'] = shop_currency_html($v['price'],  $s['currency'], $currency, false);
                    }
                    unset($v);
                }
                unset($s);
            }
            $product['sku_id'] = $item['sku_id'];


            if ($product['min_price'] == $product['max_price']) {
                $product['price_str'] = shop_currency($product['min_price'], $currency);
                $product['price_html'] = shop_currency_html($product['min_price'], $currency);
            } else {
                $product['price_str'] = shop_currency($product['min_price'], $currency).'...'.shop_currency($product['max_price'], $currency);
                $product['price_html'] = shop_currency_html($product['min_price'], $currency).'...'.shop_currency_html($product['max_price'], $currency);
            }
            $product['price'] = round($product['price'], 2);


            $config = $this->getConfig();

            $product['url_crop_small'] = shopImage::getUrl(
                array(
                    'id'         => $product['image_id'],
                    'filename'   => $product['image_filename'],
                    'product_id' => $product['id'],
                    'ext'        => $product['ext'],
                ),
                $config->getImageSize('crop_small')
            );
            $products[] = array(
                'product' => $product,
                'quantity' => $item['quantity'],
                'sku_ids' => array_keys($product['skus']),
                'service_ids' => array_keys($product['services']),
            );
        }
        return $products;
    }


    private function workupSkuStocks(&$sku, $sku_stocks)
    {
        // detaled stocks count icon for sku
        if (empty($sku_stocks[$sku['id']])) {
            $sku['icon'] = shopHelper::getStockCountIcon($sku['count'], null, true);
        } else {
            $icons = array();
            $counts_htmls = array();
            $_s = array();
            foreach ($sku_stocks[$sku['id']] as $stock_id => $stock) {
                $icon  = &$icons[$stock_id];
                $icon  = shopHelper::getStockCountIcon($stock['count'], $stock_id)." ";
                $count_html = &$counts_htmls[$stock_id];
                $count_html = _w('%d left', '%d left', $stock['count']);
                $_s[$stock_id] = $stock['count'];
                unset($icon, $count_html);
            }
            $sku['stock'] = $_s;
            $sku['icon'] = shopHelper::getStockCountIcon($sku['count'], null, true);
            $sku['icons'] = $icons;
            $sku['count_htmls'] = $counts_htmls;
        }
    }


    private function getSkuStocks($sku_ids)
    {
        if (!$sku_ids) {
            return array();
        }
        $product_stocks_model = new shopProductStocksModel();
        return $product_stocks_model->getBySkuId($sku_ids);
    }

    protected function getContact($code)
    {
        $data = array();

        $storefront_model = new shopCartsPluginStorefrontModel();
        $contact_model = new shopCartsPluginContactModel();
        $storefront_data = $storefront_model->getLastByCode($code);

        if($storefront_data['contact_id']) {
            $data['id'] = $storefront_data['contact_id'];
        } elseif($customer = $contact_model->getContactByCode($code)) {
            $data['id'] = $customer->getId();

            $fields = array(
                'firstname',
                'lastname',
                'middlename',
                'middlename',
            );

            foreach ($fields as $field) {
                $data[$field] = $customer[$field];
            }

            $multiply_fields = array(
                'email',
                'phone'
            );

            foreach ($multiply_fields as $field) {
                $data[$field] = $customer->get($field, 'default');
            }

            if($address = $customer->get('address.shipping')) {
                if(!empty($address[0]) && !empty($address[0]['data'])) {
                    $a = $address[0]['data'];
                    $keys = array(
                        'street', 'city', 'region', 'zip', 'country'
                    );
                    foreach ($keys as $k) {
                        if(!empty($a[$k])) {
                            $data['address.shipping]['.$k] = $a[$k];
                        }
                    }
                }
            }
        }
        return $data;
    }

}