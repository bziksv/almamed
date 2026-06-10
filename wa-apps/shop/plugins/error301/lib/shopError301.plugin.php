<?php 
class shopError301Plugin extends shopPlugin
{
	public function index()
	{
		$settings = $this->getSettings();
		$settings['index'] = time();
        $this->saveSettings($settings);
		$model = new shopError301Model();
		$model->index();
	}
	
	public function categorySave($category)
	{
		if(isset($category['parent_id']))
		{
			$model = new shopError301Model();
			$data = array(
				"id" => $category['id'],
				"type" => "c",
				"parent" => $category['parent_id'],
				"url" => $category['url'],
			);
			$model->insert($data, 1);
		}
		else //родитель передается только при создании категории
		{
			$model = new shopError301Model();
			$model->exec("INSERT IGNORE INTO `".$model->table."` SELECT `id`, 'c' as `type`, `url`, `parent_id` as `parent` FROM `shop_category` WHERE `id` = ".(int)$category['id'].";");
		}
	}
	
	public function categoryDelete($item)
	{
		$model = new shopError301Model();
		$model->deleteHistoryByID(array($item['id']), "c");
	}
	
	public function pageSave()
	{
		$get = waRequest::get();
		
		if(isset($get['action']) AND isset($get['module']) AND $get['action'] == 'pageSave' AND $get['module'] == 'product')
		{
			$model = new shopError301Model();
			if(isset($get['id']) AND $get['id'] > 0)
			{
				$post = waRequest::post();
				$data = array(
					"id" => $get['id'],
					"type" => "x",
					"url" => $post['info']['url'],
					"parent" => (int)$get['product_id']
				);
				$model->insert($data, 1);
			}
			else
			{
				$model->exec("INSERT IGNORE INTO `".$model->table."` SELECT `id`, 'x' as `type`, `url`, `product_id` as `parent` FROM `shop_product_pages`;");
			}
		}
	}
	
	public function productSave($params)
	{
		$model = new shopError301Model();
		$data = array(
			"id" => $params['data']['id'],
			"type" => "p",
			"url" => $params['data']['url'],
			"parent" => (int)$params['data']['category_id'],
		);
		$model->insert($data, 1);
	}
	
	public function productDelete($ids)
	{
		$model = new shopError301Model();
		$model->deleteHistoryByID($ids['ids'], "p");
		$model->deleteHistoryByID($ids['ids'], "x");
	}
	
	public function frontendError($params)
	{
		if(waRequest::get('error301', 0, 'int'))
			return false;
		
        $status = $this->getSettings();
		if(!wa()->getUser()->isAdmin())
			$status['debug'] = 0;
		
		if(!isset($status['index']) OR (time() - $status['index'] > 86400)) //некоторые изменения невозможно отследить, поэтому не чаще 1 раза в сутки индексируем
		{
			$this->index();
		}
		
		if ($params->getCode() == 404)
		{
		    $query = waRequest::server('QUERY_STRING', '');
			if(!empty($query) && mb_strpos($query, '/'.wa()->getRouting()->getCurrentUrl().'&') === 0){ //проверка для каких-то диких настроек хостинга, когда url дублируется в QUERY_STRING
				$query = trim($query, '/'.wa()->getRouting()->getCurrentUrl().'&');
			}
			
            $redirect = $this->getRedirect();
            if(empty($redirect) && $this->getSettings('root')){
                $redirect = wa()->getRootUrl(true);
            } else if(!empty($query)){
                $redirect .= '?'.$query;
                $subquery = 1;
            }
            if(!empty($status['debug']))
				echo '$redirect='.$redirect.'<br>';
			if($redirect) {
                if ($this->getSettings('check') && (ini_get('allow_url_fopen') || function_exists('curl_init'))){
                    if (empty($file_headers) && function_exists('curl_init')) {
                        $c = curl_init();
                        curl_setopt($c, CURLOPT_HEADER, true);
                        curl_setopt($c, CURLOPT_NOBODY, true);
                        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
                        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
                        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($c, CURLOPT_URL, $redirect .(empty($subquery) ? '?' : '&').'error301=1');
                        $file_headers = explode(PHP_EOL, curl_exec($c));
                        curl_close($c);
                    } else {
                        $file_headers = @get_headers($redirect .(empty($subquery) ? '?' : '&').'error301=1');
                    }
                    if (!empty($status['debug'])) {
                        echo '$file_headers=';
                        print_r($file_headers);
                        echo '<br>';
                    }

                    foreach ($file_headers as &$val) {
                        $val = trim(str_replace(array(" "), array(""), mb_strtoupper($val)));
                    }

                    if (!(
                        in_array('HTTP/0.9200OK', $file_headers) || in_array('HTTP/1.1200OK', $file_headers) || in_array('HTTP/1.0200OK', $file_headers) || in_array('HTTP/2200OK', $file_headers) ||
                        in_array('HTTP/0.9301MOVEDPERMANENTLY', $file_headers) || in_array('HTTP/1.1301MOVEDPERMANENTLY', $file_headers) || in_array('HTTP/1.0301MOVEDPERMANENTLY', $file_headers) || in_array('HTTP/2301MOVEDPERMANENTLY', $file_headers) ||
                        in_array('HTTP/0.9401UNAUTHORIZED', $file_headers) || in_array('HTTP/1.1401UNAUTHORIZED', $file_headers) || in_array('HTTP/1.0401UNAUTHORIZED', $file_headers) || in_array('HTTP/2401UNAUTHORIZED', $file_headers)
                    )) {
                        return false;
                    }
                }
                if (empty($status['debug']))
                    wa()->getResponse()->redirect($redirect, 301);
                else
                    echo 'Будет произведена переадресация на ' . $redirect;
            }
        }
	}
	
	public function getRedirect()
	{
		$status = $this->getSettings();
		if(!wa()->getUser()->isAdmin())
			$status['debug'] = 0;
		$routing = wa()->getRouting();
		$curRouting = $routing->dispatch();
		$url = $routing->getCurrentUrl();
		$search = array(
			'reviews' => false, //принадлежность адреса к отзыву
			'category' => false, //принадлежность адреса к категории
			'product' => false, //принадлежность адреса к товару
			'productpage' => false, //принадлежность адреса подстранице товара
			'url' => '', //url искомого объекта
			'parents' => array() //список родителей искомого объекта
		);
		
		if(!empty($status['debug']))
		{
			echo '$curRouting=';
			print_r($curRouting);
			echo '<br>';
			echo '$url='.$url.'<br>';
		}
		/*
		2.Естественный 
		Страницы товаров: /category-name/subcategory-name/product-name/
		Страница отзывов на товар: /category-name/subcategory-name/product-name/reviews/
		Подстраницы товаров: /category-name/subcategory-name/product-name/page-name/
		Страницы категорий: /category-name/subcategory-name/
		
		0.Смешанный 
		Страницы товаров: /product-name/
		Страница отзывов на товар: /product-name/reviews/
		Подстраницы товаров: /product-name/page-name/
		Страницы категорий: /category/category-name/subcategory-name/subcategory-name/...
		
		1.Плоский (WebAsyst Shop-Script) 
		Страницы товаров: /product/product-name/
		Страница отзывов на товар: /product/product-name/reviews/
		Подстраницы товаров: /product/product-name/page-name/
		Страницы категорий: /category/category-name/
		*/
		
		$expUrl = explode("/", $url);
		unset($expUrl[count($expUrl)-1]); //последний блок адреса тоже не нужен
		$expUrl = array_values($expUrl);
		
		if(isset($expUrl[count($expUrl) - 1]) && $expUrl[count($expUrl) - 1] == 'reviews') // если в конце адреса /reviews/, то это адрес отзыва продукта
		{
			unset($expUrl[count($expUrl) - 1]);
			$search['reviews'] = true;
			$search['product'] = true;
		}	
		
		if(isset($expUrl[0]) && $expUrl[0] == 'product') //товар, плоская адресация
		{
			unset($expUrl[0]);
			$expUrl = array_values($expUrl);
			$search['product'] = true;
			
			if(count($expUrl) == 2) //подстраница товара
			{
				$search['productpage'] = true;
				$search['parents'] = array($expUrl[0]); //url товара заносим в родителя
				$search['url'] = $expUrl[1];
			}
			elseif(count($expUrl) > 0)
			{
				$search['url'] = $expUrl[count($expUrl)-1]; //теоретически только один блок остался, но на случай совсем кривого адреса берем последний
			}			
			unset($expUrl);
		}	
		
		if(isset($expUrl[0]) AND $expUrl[0]=="category") //Категория при смешанной или плоской адресации
		{
			unset($expUrl[0]);
			if(count($expUrl) != 0)
			{
				$expUrl = array_values($expUrl);
				$search['category'] = true;
				$search['url'] = $expUrl[count($expUrl)-1];
				unset($expUrl[count($expUrl)-1]);
			}
			if(count($expUrl) != 0)
			{
				$search['parents'] = $expUrl;
			}
			unset($expUrl);
		}
		
		if(isset($expUrl[0]))
		{
			$search['url'] = $expUrl[count($expUrl)-1];
			unset($expUrl[count($expUrl)-1]);
			$search['parents'] = $expUrl;
			unset($expUrl);
		}
		$model = new shopError301Model();
		
		/*
		$item = array(
			'type' => 'x', //тип элемента (x - подстраница товара, p - продукт, с - категория)
			'id' => 1, //id элемента
			'parentID' => 1, //id родителя
			'parent' => '', //родительская часть адреса
			'url0' => '', //адрес для смешанной адресации
			'url1' => '', //адрес для плоской адресации
			'url2' => '', //адрес для естественной адресации
			'rating' => '', //релевантность найденного элемента
		);*/

		$item = $model->getItem($search);
		
		if(!empty($status['debug']))
		{
			echo '$search=';
			print_r($search);
			echo '<br>';
			echo '$item=';
			print_r($item);
			echo '<br>';
		}
			
		if($item['type'] == 'x' && $item['rating'] == '' && $item['parentID'] > 0) //подстраница удалена, а товар еще есть
		{
			$productModel = new shopProductModel();
			$product = $productModel->getById($item['parentID']);
			if(!empty($product['category_id']))
			{
				$categoryModel = new shopCategoryModel();
				$category = $categoryModel->getById($product['category_id']);
			}	
			if($product['id'] > 0)
			{
				$item['type'] = 'p';
				$item['parentID'] = $product['category_id'];
				$item['id'] = $product['id'];
				$item['url0'] = $product['url'].'/';
				$item['url1'] = 'product/'.$product['url'].'/';
				$item['url2'] = (empty($category['full_url']) ? '' : $category['full_url']).$product['url'].'/';
			}
		}

		if(empty($curRouting['url_type']))
			$curRouting['url_type'] = 0;

		if($item)
		{
			$base = wa()->getRouteUrl('shop/frontend', array(), true);
			if(!empty($status['debug']))
			{
				echo '$base='.$base.'<br>';
			}
			$newUrl = $item['url'.$curRouting['url_type']];
			
			if($search['reviews'])
				return $base.$newUrl.'reviews/';
			
			return $base.$newUrl;
		}
		else if($this->getSettings('check') && (!empty($search['category']) || ($curRouting['url_type'] == 2 && empty($search['product']) && empty($search['productpage']) && empty($search['reviews'])))) {
            return $this->getSeofilterRedirect();
        } else {
		    return false;
        }
	}

    public function getSeofilterRedirect()
    {
        $status = $this->getSettings();
        if(!wa()->getUser()->isAdmin())
            $status['debug'] = 0;
        $routing = wa()->getRouting();
        $curRouting = $routing->dispatch();
        $url = $routing->getCurrentUrl();
        $search = array(
            'url' => '', //url искомого объекта
            'category' => false, //принадлежность адреса к категории
            'product' => false, //принадлежность адреса к товару
            'productpage' => false, //
            'parents' => array() //список родителей искомого объекта
        );

        if(!empty($status['debug']))
        {
            echo '<br>$curRouting=';
            print_r($curRouting);
            echo '<br>';
            echo '$url='.$url.'<br>';
        }
        /*
        2.Естественный
        Страницы товаров: /category-name/subcategory-name/product-name/
        Страница отзывов на товар: /category-name/subcategory-name/product-name/reviews/
        Подстраницы товаров: /category-name/subcategory-name/product-name/page-name/
        Страницы категорий: /category-name/subcategory-name/

        0.Смешанный
        Страницы товаров: /product-name/
        Страница отзывов на товар: /product-name/reviews/
        Подстраницы товаров: /product-name/page-name/
        Страницы категорий: /category/category-name/subcategory-name/subcategory-name/...

        1.Плоский (WebAsyst Shop-Script)
        Страницы товаров: /product/product-name/
        Страница отзывов на товар: /product/product-name/reviews/
        Подстраницы товаров: /product/product-name/page-name/
        Страницы категорий: /category/category-name/
        */

        $expUrl = explode("/", $url);
        unset($expUrl[count($expUrl)-1]); //последний блок адреса тоже не нужен
        $expUrl = array_values($expUrl);

        if(isset($expUrl[0]) AND $expUrl[0]=="category") //Категория при смешанной или плоской адресации
        {
            unset($expUrl[0]);
            if(count($expUrl) != 0)
            {
                $expUrl = array_values($expUrl);
                //$search['category'] = true;
                $search['url'] = $expUrl[count($expUrl)-2];
                unset($expUrl[count($expUrl)-2]);
            }
            if(count($expUrl) != 0)
            {
                $search['parents'] = $expUrl;
            }
            unset($expUrl);
        }

        if(isset($expUrl[0]) && count($expUrl) > 1)
        {
            $search['url'] = $expUrl[count($expUrl)-2];
            unset($expUrl[count($expUrl)-2]);
            $search['parents'] = $expUrl;
            unset($expUrl);
        }

        $model = new shopError301Model();

        /*
        $item = array(
            'type' => 'x', //тип элемента (x - подстраница товара, p - продукт, с - категория)
            'id' => 1, //id элемента
            'parentID' => 1, //id родителя
            'parent' => '', //родительская часть адреса
            'url0' => '', //адрес для смешанной адресации
            'url1' => '', //адрес для плоской адресации
            'url2' => '', //адрес для естественной адресации
            'rating' => '', //релевантность найденного элемента
        );*/

        if(!empty($search['url'])){
            $item = $model->getItem($search);
        }

        if(!empty($status['debug']))
        {
            echo '$search=';
            print_r($search);
            echo '<br>';
            echo '$item=';
            print_r($item);
            echo '<br>';
        }

        if(empty($curRouting['url_type']))
            $curRouting['url_type'] = 0;
        if($item)
        {
            $base = wa()->getRouteUrl('shop/frontend', array(), true);
            if(!empty($status['debug']))
            {
                echo '$base='.$base.'<br>';
            }
            $newUrl = $item['url'.$curRouting['url_type']];

            if(!empty($search['parents'])) {
                return $base . $newUrl . end($search['parents']).'/';
            }
            return $base.$newUrl;
        }
        else
            return false;
    }
}