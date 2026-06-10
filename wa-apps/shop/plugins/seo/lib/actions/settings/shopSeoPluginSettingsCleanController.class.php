<?php


class shopSeoPluginSettingsCleanController extends waJsonController
{
	private $env;
	private $category_settings_service;
	
	public function __construct()
	{
		$this->env = shopSeoContext::getInstance()->getEnv();
		$this->category_settings_service = shopSeoContext::getInstance()->getCategorySettingsService();
	}
	
	public function execute()
	{
		$groups = json_decode(waRequest::request('state'), true);
		
		if ($groups['home'])
		{
			$this->cleanHomeData();
		}
		
		if ($groups['category'])
		{
			$this->cleanCategoryData();
		}
		
		if ($groups['product'])
		{
			$this->cleanProductData();
			
			if ($groups['product_review'])
			{
				$this->cleanProductReviewData();
			}
			
			if ($groups['product_page'])
			{
				$this->cleanProductPageData();
			}
		}
		
		if ($groups['page'])
		{
			$this->cleanPageData();
		}
		
		if ($groups['brand'])
		{
			$this->cleanBrandData();
		}
	}
	
	private function cleanHomeData()
	{
		$path = wa()->getConfig()->getPath('config', 'routing');
		
		if (file_exists($path))
		{
			$routes = include($path);
		}
		else
		{
			$routes = array();
		}
		
		foreach ($routes as $domain => $_routes)
		{
			foreach ($_routes as $key => $route)
			{
				if (isset($route['app']) && $route['app'] == 'shop')
				{
					$routes[$domain][$key]['title'] = '';
					$routes[$domain][$key]['meta_keywords'] = '';
					$routes[$domain][$key]['meta_description'] = '';
				}
			}
		}
		
		waUtils::varExportToFile($routes, $path);
	}
	
	private function cleanCategoryData()
	{
		$category_model = new shopCategoryModel();
		$category_model->exec("
update shop_category
set meta_title = '', meta_keywords = '', meta_description = ''
		");
		
		if ($this->env->isSupportOg())
		{
			$og_model = new shopCategoryOgModel();
			$og_model->deleteByField(array(
				'property' => array('title', 'description'),
			));
		}
		
		$category_settings_model = new shopSeoCategorySettingsModel();
		$category_settings_model->deleteByField(array(
			'name' => array('meta_title', 'meta_keywords', 'meta_description'),
		));
	}
	
	private function cleanProductData()
	{
		$product_model = new shopProductModel();
		$product_model->exec("
update shop_product
set meta_title = '', meta_keywords = '', meta_description = ''
		");
		
		if ($this->env->isSupportOg())
		{
			$og_model = new shopProductOgModel();
			$og_model->deleteByField(array(
				'property' => array('title', 'description'),
			));
		}
		
		$product_settings_model = new shopSeoProductSettingsModel();
		$product_settings_model->deleteByField(array(
			'name' => array('meta_title', 'meta_keywords', 'meta_description'),
		));
	}
	
	private function cleanProductReviewData()
	{
		$product_settings_model = new shopSeoProductSettingsModel();
		$product_settings_model->deleteByField(array(
			'name' => array('review_meta_title', 'review_meta_keywords', 'review_meta_description'),
		));
	}
	
	private function cleanProductPageData()
	{
		$product_settings_model = new shopSeoProductSettingsModel();
		$product_settings_model->deleteByField(array(
			'name' => array('page_meta_title', 'page_meta_keywords', 'page_meta_description'),
		));
	}
	
	private function cleanPageData()
	{
		$page_model = new shopPageModel();
		$page_model->exec("
update shop_page
set title = ''
		");
		
		$page_param_model = new shopPageParamsModel();
		$page_param_model->deleteByField(array(
			'name' => array('keywords', 'description')
		));
	}
	
	private function cleanBrandData()
	{
		if (!$this->env->isEnabledProductbrands())
		{
			return;
		}
		
		$brand_model = new shopProductbrandsModel();
		$brand_model->exec("
update shop_productbrands
set title = '', meta_title = '', meta_description = ''
		");
	}
}