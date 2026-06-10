<?php


class shopSeoTagDataCollector
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
			'meta_keywords',
			'meta_description',
			'description',
		));
		
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectStorefrontGroup($group_storefront, $collection);
		}
		
		$this->collectGeneral($collection);
		
		$info = $collection->getInfo();
		
		return $collection->getResult();
	}
	
	private function collectStorefrontGroup(shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection)
	{
		if ($group_storefront->getSettings()->tag_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $group_storefront->getSettings()->tag_meta_title,
				'meta_keywords' => $group_storefront->getSettings()->tag_meta_keywords,
				'meta_description' => $group_storefront->getSettings()->tag_meta_description,
				'description' => $group_storefront->getSettings()->tag_description,
			), 1, "group storefront: \"{$group_storefront->getName()}\"");
		}
	}
	
	private function collectGeneral(shopSeoLayoutsCollection $collection)
	{
		$general_settings = $this->getGeneralSettings();
		
		if ($general_settings->tag_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $general_settings->tag_meta_title,
				'meta_keywords' => $general_settings->tag_meta_keywords,
				'meta_description' => $general_settings->tag_meta_description,
				'description' => $general_settings->tag_description,
			), 1, 'general');
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
	
	private function getGeneralSettings()
	{
		return $this->storefront_settings_service->getGeneralSettings();
	}
}