<?php


class shopSeoWaCategorySaveHandler
{
	public function handle()
	{
		if (!shopSeoSettings::isEnablePlugin())
		{
			return;
		}

		$str_state = waRequest::post('shop_seo_state');

		if (!$str_state)
		{
			return;
		}

		$state = json_decode($str_state, true);
		$category_id = $state['category']['id'];
		$groups = $state['groups'];

		foreach ($groups as $storefront_id => $_groups)
		{
			foreach (shopSeoCategoryGroup::getGroups($category_id, $storefront_id) as $group)
			{
				$settings = ifset($_groups['settings'][$group->getID()], array());
				$group->setSettings($settings);

				$templates = ifset($_groups['templates'][$group->getID()], array());
				$group->setTemplates($templates);
			}

			$fields_storage = new shopSeoCategoryFieldsStorage($storefront_id, $category_id);
			$fields_storage->setValues(ifset($_groups['fields_values'], array()));
		}
	}
}