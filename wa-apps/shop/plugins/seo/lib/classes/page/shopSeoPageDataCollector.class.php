<?php


class shopSeoPageDataCollector
{
	private $page_data_source;
	private $group_storefront_service;
	private $storefront_settings_service;

	public function __construct(
		shopSeoPageDataSource $page_data_source,
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoStorefrontSettingsService $storefront_settings_service
	) {
		$this->page_data_source = $page_data_source;
		$this->group_storefront_service = $group_storefront_service;
		$this->storefront_settings_service = $storefront_settings_service;
	}

	public function collect($storefront, $page_id, &$info)
	{
		$collection = new shopSeoLayoutsCollection(array(
			'meta_title',
			'meta_keywords',
			'meta_description',
		));

		$groups_storefronts = $this->getGroupsStorefronts($storefront);

		$this->collectPersonal($page_id, $collection);

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
		$page_data = $this->page_data_source->getPageData($page_id);

		$collection->push(array(
			'meta_title' => ifset($page_data, 'title', ''),
			'meta_keywords' => ifset($page_data, 'keywords', ''),
			'meta_description' => ifset($page_data, 'description', ''),
		), 0, 'personal');
	}

	private function collectStorefrontGroup(shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection)
	{
		if ($group_storefront->getSettings()->page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $group_storefront->getSettings()->page_meta_title,
				'meta_keywords' => $group_storefront->getSettings()->page_meta_keywords,
				'meta_description' => $group_storefront->getSettings()->page_meta_description,
			), $group_storefront->getSettings()->page_ignore_meta_data ? 2 : 1, "group storefront: \"{$group_storefront->getName()}\"");
		}
	}

	private function collectGeneral(shopSeoLayoutsCollection $collection)
	{
		$general_settings = $this->getGeneralSettings();

		if ($general_settings->page_is_enabled)
		{
			$collection->push(array(
				'meta_title' => $general_settings->page_meta_title,
				'meta_keywords' => $general_settings->page_meta_keywords,
				'meta_description' => $general_settings->page_meta_description,
			), $general_settings->page_ignore_meta_data ? 2 : 1, 'general');
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