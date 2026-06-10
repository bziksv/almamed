<?php

Class shopXmlPlugin extends shopPlugin {

    protected $table = 'shop_category';

    protected $left = 'left_key';

    const TYPE_STATIC = 1;

    protected $root;


    public function __construct($info)
    {
        parent::__construct($info);
        $this->root = wa()->getConfig()->getPath('root').DIRECTORY_SEPARATOR;
    }

    public function backendMenuExport()
    {
        return array('core_li'  => '<li class="no-tab"><a href="?plugin=xml&action=edit ">Экспорт Xml для Битрикс</a></li>');
    }

    public function indexBrand($brands,$path,$percent){
        $brand = new shopProductbrandsPlugin(false);
        $self = new waNestedSetModel(false);

        $category = $this->getFullTree('',false);

        $cat_tree = array();
        $prod_tree = array();
        $cat_product = array();
		
        foreach($category as $k => $cat){
			
            //$cat_tree[$cat['parent_id']][] = $cat;
            $collection = new shopProductsCollection("category/" . $cat['id']);
            $collection->addWhere("p.status = '1'");
            $product = $collection->getProducts('*', 0, $collection->count());
			
            if ($product) {
                foreach ($product as $key_prod => $prod) {

                    $idb = $brand->productBrand($prod['id']);

                    if(in_array($idb['id'],$brands)){
                        $prod_tree[$key_prod] = $prod;
                        $sql = "SELECT * FROM shop_category_products WHERE product_id = " . $prod['id'];
                        $cat_ar = $self->query($sql)->fetchAll();
                        $prod_tree[$key_prod]['id_category'] = $cat_ar;
                        foreach($cat_ar as $c){
                            $cat_product[$c['category_id']] = $c;
                        }

                    }
                }
            }
        }


        foreach($cat_product as $id){
            $category_prod = explode(',',$this->recursive_prod_parent_cat($id['category_id']));
            foreach($category_prod as $id_cat){
                $sql = "SELECT * FROM shop_category WHERE id = " . $id_cat;
                $cat_ar = $self->query($sql)->fetchAll();
                $cat_tree[$cat_ar[0]['parent_id']][$cat_ar[0]['id']] = $cat_ar[0];
            }
        }




        $this->saveXml($this->create_tree($cat_tree,0),$this->create_product($prod_tree,$percent),$path);

        return true;

    }


    public function recursive_prod_parent_cat($id_cat){

            $arr = '';
            $self = new waNestedSetModel();

            $sql = "SELECT * FROM shop_category WHERE id = " . $id_cat;
            $cat_ar = $self->query($sql)->fetch();

            if($cat_ar['parent_id'] > 0){
                $arr .= $cat_ar['id'].',';
                $arr .= $this->recursive_prod_parent_cat($cat_ar['parent_id']);
            }else{
                $arr .= $id_cat;
            }

        return $arr;
    }


    public function indexCat($cat,$path,$percent)
    {
        //получаем категории которые нужно убрать.

        $cat_tree = array();
        $prod_tree = array();

        $category = $this->getFullTree('',false,$cat);

        foreach($category as $k => $cat){
            $cat_tree[$cat['parent_id']][] = $cat;
        }

        $products_cat = substr($this->clear_tree($cat_tree,0), 0, -1);
        foreach(explode(',',$products_cat) as $cat){
            $self = new waNestedSetModel();
            $collection = new shopProductsCollection("category/" . $cat);
            $collection->addWhere("p.status = '1'");
            $product = $collection->getProducts("*",0,10000);
            if ($product) {
                foreach ($product as $key_prod => $prod) {
                    $prod_tree[$key_prod] = $prod;
                    $sql = "SELECT * FROM shop_category_products WHERE product_id = " . $prod['id'];
                    $cat_ar = $self->query($sql)->fetchAll();
                    $prod_tree[$key_prod]['id_category'] = $cat_ar;
                }
            }
        }

        $this->saveXml($this->create_tree($cat_tree,0),$this->create_product($prod_tree,$percent),$path,$percent);

        return true;
    }

    public function clear_tree($cats,$parent_id){

        if(is_array($cats) and  isset($cats[$parent_id])){
          $id = '';
            foreach($cats[$parent_id] as $cat){
                $id .= $cat['id'];
                $id .= ",";
                $id .= $this->clear_tree($cats,$cat['id']);
            }
        }
        else return null;
        return $id;
    }


    public function getFullTree($fields = '', $status = true, $select = array(),$parent = false)
    {
        $self = new waNestedSetModel();

        if (!$fields) {
            $fields = 'id, url, left_key, right_key, parent_id, depth, name, count, type, status';
        }
        if($select){
            $del = '';
            foreach($select as $sel){
                $del .= 'AND id != ' . $sel.' ';
            }
        }

        if($parent){
            $parent = ' AND parent_id = 0 ';
        }

        $fields = $self->escape($fields);

        $sql = "SELECT $fields FROM {$this->table} WHERE id != '' $del $parent ORDER BY {$this->left}";

        return $self->query($sql)->fetchAll('id');
    }


    public function create_tree($cats,$parent_id){

        if(is_array($cats) and  isset($cats[$parent_id])){
            $tree = '<Группы>';
            foreach($cats[$parent_id] as $cat){

                $tree .= "<Группа>";
                $tree .= "<Ид>".$cat['id']."</Ид>";
                $tree .= "<БитриксКод>".str_replace('/','',$cat['url'])."</БитриксКод>";
                $tree .= "<ПометкаУдаления>false</ПометкаУдаления>";
                $tree .= "<БитриксАктивность>".$cat['status']."</БитриксАктивность>";
                $tree .= "<Наименование>". shopXmlPlugin::cleanText($cat['name']) ."</Наименование>";
                $tree .=  $this->create_tree($cats,$cat['id']);
                $tree .= '</Группа>';
            }

            $tree .= '</Группы>';

        }
        else return null;


        return $tree;
    }


    public function create_product($product,$percent){
        $this->images_import($product);
        $view = wa()->getView();
        $view->assign('product', $product);
        $view->assign('percent', $percent);
        return $view->fetch($this->root.'wa-apps/shop/plugins/xml/templates/product.html');
    }

    public function images_import($product){
        $archive = 'import_files.zip';
        if (file_exists($archive)) {
            unlink($archive);
        }
        $zip = new ZipArchive;
        if ($res = $zip->open($archive, ZipArchive::CREATE) === TRUE) {
        foreach($product as $key => $prod){
            $product_img = new shopProduct($prod[id]);
            $images = $product_img->getImages('big');
                foreach($images as $img){
                    $image = stristr(shopImage::getPath(['product_id' => $prod[id], 'id' => $img[id],'ext' => $img[ext]]),"wa-data");
                    $zip->addFile($image,$img[id].'.'.$img[ext]);
                }
            }
        }
        $zip->close();
    }


    public function saveXml($cat,$prod,$path){

        $view = wa()->getView();
        $view->assign('cat', $cat);
        $view->assign('prod', $prod);

        $tree = $view->fetch($this->root.'wa-apps/shop/plugins/xml/templates/body.html');

        if($path){
            file_put_contents($path, $tree);
        }else{
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/import.xml', $tree);
        }

    }
    
	function cleanText($text) 
	{    
		$text = html_entity_decode($text);
		
		$text = strip_tags($text);
		
		$text = str_replace(["&", "<", ">"], ["and", "", ""], $text);
    
		return trim($text);
	}

}