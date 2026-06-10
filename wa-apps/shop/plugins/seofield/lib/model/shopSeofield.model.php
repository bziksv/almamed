<?php

class shopSeofieldModel extends waModel
{
    protected $product_table = 'shop_product';

    protected $category_table = 'shop_category';

    protected $seo_product_table = 'shop_seo_product_settings';

    protected $seo_category_table = 'shop_seo_category_settings';

    protected $product_pivot = "product_id";

    protected $category_pivot = "category_id";

    protected $seo_filter = "sp.name = 'seo_name' OR sp.name = 'h1'";

    protected $filter;

    protected $offset;

    protected $limit;

    protected $count = [];


    protected function filter($request = null){

        if($request == 'seo_name')
            $this->filter = "sp.name = 'seo_name'";
        elseif ($request == 'h1')
            $this->filter = "sp.name = 'h1'";
        else
            $this->filter = $this->seo_filter;
    }

    public function getCategory($request = null, $offset, $limit){

        $this->filter($request);

        $sql = $this->sql('category');

        $sql .= "\nLIMIT ".($offset ? $offset.',' : '').(int)$limit;

        $data = $this->query($sql)->fetchAll('id');
        if (!$data) {
            return array();
        }

        return $data;
    }

    public function getProducts($request = null, $offset, $limit){

        $this->filter($request);

        $sql = $this->sql('product');

        $sql .= "\nLIMIT ".($offset ? $offset.',' : '').(int)$limit;

        $data = $this->query($sql)->fetchAll('id');
        if (!$data) {
            return array();
        }

        $this->image($data);
        return $data;
    }

    public function getCount(){

        return $this->count;
    }

    protected function sql($type = null){

        if($type == 'product'){

            $table = $this->product_table;
            $table_join = $this->seo_product_table;
            $column = $this->product_pivot;
        }elseif ('category'){

            $table = $this->category_table;
            $table_join = $this->seo_category_table;
            $column = $this->category_pivot;
        }else{
            return '';
        }

        $sql = "SELECT p.*, sp1.value AS seo_name, sp2.value AS h1 FROM ". $table ." p\n";
        $sql .= "\nLEFT JOIN ". $table_join ." sp1 ON p.id = sp1.". $column ." AND sp1.name = 'seo_name'";
        $sql .= "\nLEFT JOIN ". $table_join ." sp2 ON p.id = sp2.". $column ." AND sp2.name = 'h1'";
        $sql .= "\nWHERE p.id ";
        $sql .= "\nIN(SELECT sp.". $column ." FROM ". $table_join ." sp WHERE ". $this->filter ." GROUP BY sp.". $column .")";

        $this->count[$type] = $this->query($sql)->count();

        return $sql;
    }

    protected function image(&$products = array()){

        if (empty($products)) {
            return;
        }

        // Round prices for products
        $config = wa('shop')->getConfig();

        $size = $config->getImageSize('crop_small');
        foreach ($products as &$p) {
            if ($p['image_id']) {
                $tmp = array(
                    'id'         => $p['image_id'],
                    'product_id' => $p['id'],
                    'filename'   => $p['image_filename'],
                    'ext'        => $p['ext']
                );
                $p['image_crop_small'] = shopImage::getUrl($tmp, $size, isset($this->options['absolute']) ? $this->options['absolute'] : false);
            }
        }
        unset($p);
    }



}
