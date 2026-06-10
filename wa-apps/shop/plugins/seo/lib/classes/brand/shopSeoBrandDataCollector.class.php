<?php


class shopSeoBrandDataCollector
{
	private $brand_data_source;
	private $group_storefront_service;
	private $storefront_settings_service;
	
	public function __construct(
		shopSeoBrandDataSource $brand_data_source,
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoStorefrontSettingsService $storefront_settings_service
	) {
		$this->brand_data_source = $brand_data_source;
		$this->group_storefront_service = $group_storefront_service;
		$this->storefront_settings_service = $storefront_settings_service;
	}
	
	
	public function collect($storefront, $brand_id, &$info)
	{
		$collection = new shopSeoLayoutsCollection(array(
			'meta_title',
			'h1',
			'meta_keywords',
			'meta_description',
			'description'
		));
		
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		
		$this->collectPersonal($brand_id, $collection);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectStorefrontGroup($group_storefront, $collection);
		}
		
		$this->collectGeneral($collection);
		
		$info = $collection->getInfo();
		
		return $collection->getResult();
	}
	
	private function collectPersonal($brand_id, shopSeoLayoutsCollection $collection)
	{
		$row = $this->brand_data_source->getBrandData($brand_id);
		
		$collection->push(array(
			'meta_title' => isset($row['title']) ? $row['title'] : '',
			'h1' => isset($row['h1'])
				? $row['h1'] : '',
			'meta_keywords' => isset($row['meta_keywords'])
				? $row['meta_keywords'] : '',
			'meta_description' => isset($row['meta_description'])
				? $row['meta_description'] : '',
			'description' => isset($row['description'])
				? $row['description'] : '',
		), 0, 'personal');
	}
	
	private function collectStorefrontGroup(
		shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection
	) {
		$settings = $group_storefront->getSettings();
		
		if ($settings->brand_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $settings->brand_meta_title,
				'h1' => $settings->brand_h1,
				'meta_keywords' => $settings->brand_meta_keywords,
				'meta_description' => $settings->brand_meta_description,
				'description' => $settings->brand_description,
			), $settings->brand_ignore_meta_data ? 2 : 1, "group storefront: \"{$group_storefront->getName()}\"");
		}
	}
	
	private function collectGeneral(shopSeoLayoutsCollection $collection)
	{
		$general_settings = $this->storefront_settings_service->getGeneralSettings();
		
		if ($general_settings->brand_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $general_settings->brand_meta_title,
				'h1' => $general_settings->brand_h1,
				'meta_keywords' => $general_settings->brand_meta_keywords,
				'meta_description' => $general_settings->brand_meta_description,
				'description' => $general_settings->brand_description,
			), $general_settings->brand_ignore_meta_data ? 2 : 1, 'general');
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
}