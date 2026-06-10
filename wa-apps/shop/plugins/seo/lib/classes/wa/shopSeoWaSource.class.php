<?php


class shopSeoWaSource implements shopSeoHomeMetaDataSource, shopSeoCategoryDataSource, shopSeoPageDataSource,
	shopSeoBrandDataSource, shopSeoProductDataSource
{
	private $category_model;
	private $category_params_model;
	private $category_og_model;
	private $plugin_settings_service;
	private $page_model;
	private $page_params_model;
	private $product_model;
	private $product_page_model;
	private $env;
	private $productbrands_model;
	
	public function __construct(
		shopCategoryModel $category_model,
		shopCategoryParamsModel $category_params_model,
		shopCategoryOgModel $category_og_model,
		shopSeoPluginSettingsService $plugin_settings_service,
		shopPageModel $page_model,
		shopPageParamsModel $page_params_model,
		shopProductModel $product_model,
		shopProductPagesModel $product_pages_model,
		shopSeoEnv $env,
		shopProductbrandsModel $productbrands_model = null
	) {
		$this->category_model = $category_model;
		$this->category_params_model = $category_params_model;
		$this->category_og_model = $category_og_model;
		$this->plugin_settings_service = $plugin_settings_service;
		$this->page_model = $page_model;
		$this->page_params_model = $page_params_model;
		$this->product_model = $product_model;
		$this->product_page_model = $product_pages_model;
		$this->env = $env;
		$this->productbrands_model = $productbrands_model;
	}
	
	public function getHomeMetaData($storefront)
	{
		list($domain, $route) = explode('/', $storefront);
		
		$routes = wa()->getRouting()->getRoutes($domain);
		$found_route = null;
		
		foreach ($routes as $_route)
		{
			if (is_array($_route) && array_key_exists('url', $_route) && $_route['url'] == $route)
			{
				$found_route = $_route;
				break;
			}
		}
		
		if (is_null($found_route))
		{
			return array(
				'meta_title' => '',
				'meta_description' => '',
				'meta_keywords' => '',
			);
		}
		
		return array(
			'meta_title' => $found_route['title'],
			'meta_description' => $found_route['meta_description'],
			'meta_keywords' => $found_route['meta_keywords'],
		);
	}
	
	public function getCategoryIds()
	{
		$category_ids = array();
		
		foreach ($this->category_model->select('id')->order('left_key asc')->query() as $row)
		{
			$category_ids[] = $row['id'];
		}
		
		return $category_ids;
	}
	
	public function getCategoryData($category_id)
	{
		$category = wa()->getView()->getVars('category');
		
		if (!(is_array($category) && isset($category['id']) && $category['id'] == $category_id))
		{
			$category = $this->category_model->getById($category_id);
			
			if (is_null($category))
			{
				return null;
			}
			
			if ($this->env->isSupportOg())
			{
				$category['og'] = $this->category_og_model->get($category['id']) + array(
						'type' => 'article',
						'title' => $category['meta_title'],
						'description' => $category['meta_description'],
						'url' => wa()->getConfig()->getHostUrl() . wa()->getConfig()->getRequestUrl(false, true),
						'image' => '',
					);
			}
		}
		
		if ($this->env->isEnabledMyland())
		{
			$rows = mylangViewHelper::categories(array($category_id => $category));
			$category = reset($rows);
		}
		
		return $category;
	}
	
	public function getCategoryPath($category_id)
	{
		return $this->category_model->getPath($category_id);
	}
	
	public function getCategoryProductsData($storefront, $category_id)
	{
		if ($this->plugin_settings_service->getSettings()->cache_is_enabled)
		{
			/** @var shopConfig $config */
			$config = wa('shop')->getConfig();
			$default_currency = $config->getCurrency(true);
			$frontend_currency = $config->getCurrency(false);

			$hidden_settings = $this->plugin_settings_service->getHiddenSettings();
			
			$key = json_encode(array(
				'storefront' => $hidden_settings->isCacheForSingleStorefront() ? '*' : $storefront,
				'default_currency' => $default_currency,
				'frontend_currency' => $frontend_currency,
			));
			
			$cache = $this->getCacheCategoryProductsData($category_id);
			
			if ($cache->isCached())
			{
				$data = $cache->get();
			}
			else
			{
				$data = array();
			}
			
			if (!array_key_exists($key, $data))
			{
				$data[$key] = $this->_getCategoryProductsData($category_id);
				$cache->set($data);
			}
			
			return $data[$key];
		}
		
		return $this->_getCategoryProductsData($category_id);
	}
	
	public function updateByCategoryId($category_id, $row)
	{
		$this->category_model->updateById($category_id, $row);
	}

	public function isCategoryStatic($category_id)
	{
		$category_type = $this->category_model
			->select('type')
			->where('id = :category_id', array('category_id' => $category_id))
			->fetchField();

		return $category_type !== NULL && intval($category_type) === shopCategoryModel::TYPE_STATIC;
	}

	public function getCategoryParams($category_id)
	{
		return $this->category_params_model->get($category_id);
	}

	private function getCacheCategoryProductsData($category_id)
	{
		$ttl = $this->plugin_settings_service->getSettings()->cache_variant * 60;
		$ttl *= 1 + rand(-25, 25) * 0.01;
		
		return new waSerializeCache(
			'seo/category_products_data_' . $category_id,
			$ttl,
			'shop'
		);
	}
	
	private function _getCategoryProductsData($category_id)
	{
		$products = new shopSeoWaProductsCollection('category/' . $category_id);
		
		$result = array();
		$result['products_count'] = $products->count();
		
		$range = $products->getPriceRange();
		
		$range['min'] = $this->roundPrice($range['min']);
		$result['min_price'] = shop_currency($range['min']);
		$result['min_price_without_currency'] = $range['min'];
		
		$range['max'] = $this->roundPrice($range['max']);
		$result['max_price'] = shop_currency($range['max']);
		$result['max_price_without_currency'] = $range['max'];
		
		return $result;
	}
	
	private function roundPrice($price)
	{
		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();
		$curs = $config->getCurrencies();
		$default_currency = $config->getCurrency(true);
		$frontend_currency = $config->getCurrency(false);
		
		if ($price > 0)
		{
			$frontend_price = shop_currency($price, $default_currency, $frontend_currency, false);
			
			if (!empty($curs[$frontend_currency]['rounding']) && $default_currency != $frontend_currency)
			{
				$frontend_price = shopRounding::roundCurrency($frontend_price, $frontend_currency);
				$price = shop_currency($frontend_price, $frontend_currency, $default_currency, false);
			}
		}
		
		return $price;
	}
	
	public function getPageData($page_id)
	{
		$page_data = $this->page_model->getById($page_id);
		
		if (!$page_data)
		{
			return null;
		}
		
		$params = $this->page_params_model->getById($page_id);
		
		if ($params)
		{
			$page_data += $params;
		}
		
		return $page_data;
	}
	
	public function getBrandData($brand_id)
	{
		$brand = wa()->getView()->getVars('brand');
		
		if (!$brand || $brand['id'] != $brand_id)
		{
			$brand = $this->productbrands_model->getBrand($brand_id);
		}
		
		return $brand;
	}
	
	public function getProductIds()
	{
		$product_ids = array();
		
		foreach ($this->product_model->select('id')->order('id asc')->query() as $row)
		{
			$product_ids[] = $row['id'];
		}
		
		return $product_ids;
	}
	
	public function getProductData($product_id)
	{
		$product = wa()->getView()->getVars('product');
		
		if (!(isset($product) && (is_array($product) || $product instanceof shopProduct) && isset($product['id']) && $product['id'] == $product_id))
		{
			$product = new shopProduct($product_id);
			
			if (!$product->getId())
			{
				return null;
			}
		}
		
		if ($this->env->isEnabledMyland())
		{
			$rows = mylangViewHelper::products(array($product_id => $product));
			$product = reset($rows);
		}
		
		return $product;
	}
	
	public function getProductCategoryId($product_id)
	{
		$product = $this->getProductData($product_id);
		
		if (!$product)
		{
			return null;
		}
		
		if ($product['category_id'])
		{
			return $product['category_id'];
		}
		
		return $this->getClosestDynamicCategoryByProductID($product['id']);
	}
	
	public function getProductPage($page_id)
	{
		$this->product_page_model->get($page_id);
	}
	
	private function getClosestDynamicCategoryByProductID($product_id)
	{
		$categories = $this->category_model
			->select('id, conditions')
			->where('type=?', shopCategoryModel::TYPE_DYNAMIC)
			->order('depth desc')
			->fetchAll();
		$category_id = null;
		
		foreach ($categories as $category)
		{
			$conditions = $this->getDynamicCategoryConditions($category_id['id']);
			$pc = new shopProductsCollection('search/id=' . $product_id . '&' . $conditions);
			
			if ($pc->count() == 1)
			{
				$category_id = $category['id'];
				break;
			}
		}
		
		return $category_id;
	}
	
	private function getDynamicCategoryConditions($category_id)
	{
		$category_model = new shopCategoryModel();
		$category = $category_model
			->select('id, parent_id, conditions, type')
			->where('id=?', $category_id)
			->fetchAssoc();
		$conditions = array();
		$conditions[] = $category['conditions'];
		
		while ($category['parent_id'])
		{
			$category = $category_model
				->select('id, parent_id, conditions, type')
				->where('id=?', $category['parent_id'])
				->fetchAssoc();
			
			if ($category['type'] == shopCategoryModel::TYPE_DYNAMIC)
			{
				$conditions[] = $category['conditions'];
			}
			else
			{
				$conditions[] = 'category_id=' . $category['id'];
				break;
			}
		}
		
		return implode('&', $conditions);
	}
}
