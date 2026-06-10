<?php


class shopSeoProductPageDataCollector
{
	private $product_data_source;
	private $category_data_source;
	private $group_storefront_service;
	private $storefront_settings_service;
	private $group_category_service;
	private $product_settings_service;
	private $category_settings_service;
	private $plugin_settings_service;
	
	public function __construct(
		shopSeoProductDataSource $product_data_source,
		shopSeoCategoryDataSource $category_data_source,
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoStorefrontSettingsService $storefront_settings_service,
		shopSeoGroupCategoryService $group_category_service,
		shopSeoProductSettingsService $product_settings_service,
		shopSeoCategorySettingsService $category_settings_service,
		shopSeoPluginSettingsService $plugin_settings_service
	) {
		$this->product_data_source = $product_data_source;
		$this->category_data_source = $category_data_source;
		$this->group_storefront_service = $group_storefront_service;
		$this->storefront_settings_service = $storefront_settings_service;
		$this->group_category_service = $group_category_service;
		$this->product_settings_service = $product_settings_service;
		$this->category_settings_service = $category_settings_service;
		$this->plugin_settings_service = $plugin_settings_service;
	}
	
	public function collect($storefront, $product_id, $page_id, &$info)
	{
		$keys = array(
			'meta_title',
			'h1',
			'meta_keywords',
			'meta_description',
		);
		
		$collection = new shopSeoLayoutsCollection($keys);
		
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		$this->collectPersonal($product_id, $collection);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectPersonalStorefrontGroup($product_id, $group_storefront, $collection);
		}
		
		$this->collectPersonalGeneral($product_id, $collection);
		
		$category_id = $this->product_data_source->getProductCategoryId($product_id);
		
		if ($category_id)
		{
			$category = $this->category_data_source->getCategoryData($category_id);
			$path_categories = array_merge(array($category),
				$this->category_data_source->getCategoryPath($category['id']));
			
			foreach ($path_categories as $path_category)
			{
				foreach ($groups_storefronts as $group_storefront)
				{
					$this->collectProductsCategoryStorefrontGroup($path_category, $group_storefront, $collection);
				}
				
				$this->collectProductsCategoryGeneral($path_category, $collection);
				
				$path_groups_categories = $this->getGroupsCategories($storefront, $path_category['id']);
				
				foreach ($path_groups_categories as $path_group_category)
				{
					$this->collectProductsCategoryGroup($path_group_category, $collection);
				}
			}
		}
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectStorefrontGroup($group_storefront, $collection);
		}
		
		$this->collectGeneral($collection);
		
		$info = $collection->getInfo();
		
		return $collection->getResult();
	}
	
	private function collectPersonal($page_id, shopSeoLayoutsCollection $collection)
	{
		$page_data = $this->product_data_source->getProductPage($page_id);
		
		$collection->push(array(
			'meta_title' => ifset($page_data, 'title', ''),
			'meta_keywords' => ifset($page_data, 'keywords', ''),
			'meta_description' => ifset($page_data, 'description', ''),
		), 0, 'personal');
	}
	
	private function collectPersonalStorefrontGroup($product_id, shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection)
	{
		$settings = $this->product_settings_service->getByGroupStorefrontIdAndProductId(
			$group_storefront->getId(), $product_id
		);
		
		$comment = "personal; storefront group: \"{$group_storefront->getName()}\"";
		
		if ($settings->page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->page_meta_title,
				'h1' => $settings->page_h1,
				'meta_keywords' => $settings->page_meta_keywords,
				'meta_description' => $settings->page_meta_description,
			), $settings->page_ignore_meta_data ? 2 : 1, $comment);
		}
	}
	
	private function collectPersonalGeneral($product_id, shopSeoLayoutsCollection $collection)
	{
		$settings = $this->product_settings_service->getGeneralByProductId(
			$product_id
		);
		
		$comment = 'personal; general';
		
		if ($settings->page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->page_meta_title,
				'h1' => $settings->page_h1,
				'meta_keywords' => $settings->page_meta_keywords,
				'meta_description' => $settings->page_meta_description,
			), $settings->page_ignore_meta_data ? 2 : 1, $comment);
		}
	}
	
	private function collectProductsCategoryStorefrontGroup($path_category, shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection)
	{
		if (!$this->plugin_settings_service->getSettings()->category_products_is_enabled)
		{
			return;
		}
		
		$settings = $this->category_settings_service->getByGroupStorefrontIdAndCategoryId(
			$group_storefront->getId(),
			$path_category['id']
		);
		
		$comment = "products; category: \"{$path_category['name']}\"; group storefront: \"{$group_storefront->getName()}\"";
		
		if ($settings->product_is_enabled && $settings->product_page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->product_page_meta_title,
				'h1' => $settings->product_page_h1,
				'meta_keywords' => $settings->product_page_meta_keywords,
				'meta_description' => $settings->product_page_meta_description,
			), $settings->product_page_ignore_meta_data ? 2 : 1, $comment);
		}
	}
	
	private function collectProductsCategoryGeneral($path_category, shopSeoLayoutsCollection $collection)
	{
		if (!$this->plugin_settings_service->getSettings()->category_products_is_enabled)
		{
			return;
		}
		
		$settings = $this->category_settings_service->getGeneralByCategoryId(
			$path_category['id']
		);
		
		$comment = "products; category: \"{$path_category['name']}\"; general";
		
		if ($settings->product_is_enabled && $settings->product_page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->product_page_meta_title,
				'h1' => $settings->product_page_h1,
				'meta_keywords' => $settings->product_page_meta_keywords,
				'meta_description' => $settings->product_page_meta_description,
			), $settings->product_page_ignore_meta_data ? 2 : 1, $comment);
		}
	}
	
	private function collectProductsCategoryGroup(shopSeoGroupCategory $group_category, shopSeoLayoutsCollection $collection)
	{
		if (!$this->plugin_settings_service->getSettings()->category_products_is_enabled)
		{
			return;
		}
		
		$settings = $group_category->getSettings();
		
		$comment = "products; group category: \"{$group_category->getName()}\"";
		
		if ($settings->product_is_enabled && $settings->product_page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->product_page_meta_title,
				'h1' => $settings->product_page_h1,
				'meta_keywords' => $settings->product_page_meta_keywords,
				'meta_description' => $settings->product_page_meta_description,
			), $settings->product_page_ignore_meta_data ? 2 : 1, $comment);
		}
	}
	
	private function collectStorefrontGroup(shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection)
	{
		$settings = $group_storefront->getSettings();
		
		$comment = "group storefront: \"{$group_storefront->getName()}\"";
		
		if ($settings->product_is_enabled && $settings->product_page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->product_page_meta_title,
				'h1' => $settings->product_page_h1,
				'meta_keywords' => $settings->product_page_meta_keywords,
				'meta_description' => $settings->product_page_meta_description,
			), $settings->product_page_ignore_meta_data ? 2 : 1, $comment);
		}
	}
	
	private function collectGeneral(shopSeoLayoutsCollection $collection)
	{
		$settings = $this->storefront_settings_service->getGeneralSettings();
		
		$comment = "general";
		
		if ($settings->product_is_enabled && $settings->product_page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->product_page_meta_title,
				'h1' => $settings->product_page_h1,
				'meta_keywords' => $settings->product_page_meta_keywords,
				'meta_description' => $settings->product_page_meta_description,
			), $settings->product_page_ignore_meta_data ? 2 : 1, $comment);
		}
	}
	
	private function getGroupsStorefronts($storefront)
	{
		$groups_storefronts = $this->group_storefront_service->getByStorefront($storefront);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			if (!$group_storefront->getSettings())
			{
				$this->group_storefront_service->loadSettings($group_storefront);
			}
		}
		
		return $groups_storefronts;
	}
	
	private function getGroupsCategories($storefront, $category_id)
	{
		$groups_categories = $this->group_category_service->getByStorefrontAndCategoryId($storefront, $category_id);
		
		foreach ($groups_categories as $group_category)
		{
			if (!$group_category->getSettings())
			{
				$this->group_category_service->loadSettings($group_category);
			}
		}
		
		return $groups_categories;
	}
}