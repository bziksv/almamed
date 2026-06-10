<?php


class shopCartsPluginViewHelper {

    protected $code;
    protected $url;
    protected $domain;
    protected $scheme;


    public function __construct($code, $storefront)
    {
        $plugin = wa()->getPlugin('carts');
        $this->scheme = $plugin->getSettings('message_https') ? 'https' : 'http';
        $this->code = $code;
        $storefront = trim($storefront, '/*');
        $this->url = $this->scheme . '://'.$storefront.'/';

        $domains = wa()->getRouting()->getDomains();
        foreach($domains as $domain) {
            if(strpos($storefront, $domain) !== false) {
                $this->domain = $domain;
            }
        }
        if(empty($this->domain)) $this->domain = $domains[0];
    }

    public function cancelUrl()
    {
        return $this->url.'cartscancel/'.$this->code.'/';
    }

    public function restoreUrl()
    {
        return $this->url.'cartsrestore/'.$this->code.'/';
    }

    public function waAppUrl()
    {
        return $this->url;
    }

    public function waUrl()
    {
        return $this->scheme.'://'.$this->domain.'/';
    }

    public function domain()
    {
        return $this->domain;
    }

    public function productUrl($product)
    {
        $routing_params = array(
            'product_url' => $product['url'],
            'category_url' => $product['category_url']
        );
        return wa()->getRouting()->getUrl('shop/frontend/product', $routing_params, true, $this->domain, null);
    }

    public function productImgUrl($product, $size)
    {
        if (!$product['image_id']) {
            return '';
        }


        $image = array(
            'product_id' => $product['id'],
            'id' => $product['image_id'],
            'ext' => $product['ext'],
            'filename' => ifempty($product['image_filename'])
        );

        if(method_exists('shopImage','getUrl')) {
            $_url = shopImage::getUrl($image, $size, false);
            $_url = preg_replace('/^.*wa-data\/public\/shop\/products/', '/wa-data/public/shop/products', $_url);
        } else {
            $path = shopProduct::getFolder($image['product_id'])."/{$image['product_id']}/images/{$image['id']}/{$image['id']}.{$size}.{$image['ext']}";

            if (!waSystemConfig::systemOption('mod_rewrite')) {
                $path = str_replace('products/', 'products/thumb.php/', $path);
            }
            $_url = '/wa-data/public/shop/'.$path;
        }

        return $this->scheme.'://'.$this->domain.$_url;


    }

    public function productImgHtml($product, $size, $attributes = array())
    {
        if (!$product['image_id']) {
            if (!empty($attributes['default'])) {
                return '<img src="'.$attributes['default'].'">';
            }
            return '';
        }
        if (!empty($product['image_desc']) && !isset($attributes['alt'])) {
            $attributes['alt'] = htmlspecialchars($product['image_desc']);
        }
        if (!empty($product['image_desc']) && !isset($attributes['title'])) {
            $attributes['title'] = htmlspecialchars($product['image_desc']);
        }
        $html = '<img';
        foreach ($attributes as $k => $v) {
            if ($k != 'default') {
                $html .= ' '.$k.'="'.$v.'"';
            }
        }

        $html .= ' src="'.$this->productImgUrl($product, $size).'">';
        return $html;
    }

    public function stop($message)
    {
        throw new shopCartsPluginStopMessageException($message);
    }
}