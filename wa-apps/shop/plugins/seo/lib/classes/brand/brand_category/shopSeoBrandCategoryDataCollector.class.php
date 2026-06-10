<?php


class shopSeoBrandCategoryDataCollector
{
	private $group_storefront_service;
	private $storefront_settings_service;
	
	public function __construct(
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoStorefrontSettingsService $storefront_settings_service
	) {
		$this->group_storefront_service = $group_storefront_service;
		$this->storefront_settings_service = $storefront_settings_service;
	}
	
	public function collect($storefront, &$info)
	{
		$collection = new shopSeoLayoutsCollection(array(
			'meta_title',
			'h1',
			'meta_keywords',
			'meta_description',
			'description',
		));
		
		$groups_storefronts = $this->getGroupStorefronts($storefront);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectGroupStorefront($group_storefront, $collection);
		}
		
		$this->collectGeneral($collection);
		
		$info = $collection->getInfo();
		
		return $collection->getResult();
	}
	
	private function collectGroupStorefront(
		shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection
	) {
		$settings = $group_storefront->getSettings();
		
		if ($settings->brand_is_enabled && $settings->brand_category_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->brand_category_meta_title,
				'h1' => $settings->brand_category_h1,
				'meta_description' => $settings->brand_category_meta_description,
				'meta_keywords' => $settings->brand_category_meta_keywords,
				'description' => $settings->brand_category_description,
			), 1, "group storefront: \"{$group_storefront->getName()}\"");
		}
	}
	
	private function collectGeneral(shopSeoLayoutsCollection $collection)
	{
		$settings = $this->storefront_settings_service->getGeneralSettings();
		
		if ($settings->brand_is_enabled && $settings->brand_category_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->brand_category_meta_title,
				'h1' => $settings->brand_category_h1,
				'meta_description' => $settings->brand_category_meta_description,
				'meta_keywords' => $settings->brand_category_meta_keywords,
				'description' => $settings->brand_category_description,
			), 1, "general");
		}
	}
	
	private function getGroupStorefronts($storefront)
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
}