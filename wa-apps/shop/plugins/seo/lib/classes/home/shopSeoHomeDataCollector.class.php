<?php


class shopSeoHomeDataCollector
{
	private $home_meta_data_source;
	private $group_storefront_service;
	private $storefront_settings_service;
	
	public function __construct(
		shopSeoHomeMetaDataSource $home_meta_data_source,
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoStorefrontSettingsService $storefront_settings_service
	) {
		$this->home_meta_data_source = $home_meta_data_source;
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
		
		$this->collectPersonal($storefront, $collection);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectGroupStorefront($group_storefront, $collection);
		}
		
		$this->collectGeneralGroup($collection);
		
		$info = $collection->getInfo();
		
		return $collection->getResult();
	}
	
	private function collectPersonal($storefront, shopSeoLayoutsCollection $collection)
	{
		$meta_data = $this->home_meta_data_source->getHomeMetaData($storefront);
		$collection->push(array(
			'meta_title' => $meta_data['meta_title'],
			'meta_keywords' => $meta_data['meta_keywords'],
			'meta_description' => $meta_data['meta_description'],
		), 0, 'personal');
	}
	
	/**
	 * @param shopSeoGroupStorefront $group_storefront
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGroupStorefront($group_storefront, shopSeoLayoutsCollection $collection)
	{
		if ($group_storefront->getSettings()->home_page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $group_storefront->getSettings()->home_page_meta_title,
				'meta_keywords' => $group_storefront->getSettings()->home_page_meta_keywords,
				'meta_description' => $group_storefront->getSettings()->home_page_meta_description,
				'description' => $group_storefront->getSettings()->home_page_description,
			), 1, "group storefront: \"{$group_storefront->getName()}\"");
		}
	}
	
	private function collectGeneralGroup(shopSeoLayoutsCollection $collection)
	{
		$general_settings = $this->getGeneralSettings();
		
		if ($general_settings->home_page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $general_settings->home_page_meta_title,
				'meta_keywords' => $general_settings->home_page_meta_keywords,
				'meta_description' => $general_settings->home_page_meta_description,
				'description' => $general_settings->home_page_description,
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