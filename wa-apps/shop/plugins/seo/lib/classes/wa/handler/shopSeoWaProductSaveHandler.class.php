<?php


class shopSeoWaProductSaveHandler
{
	public function handle($data)
	{
		$is_valid_data = isset($data['data']['id']) && waRequest::post('shop_seo_state');

		if (!shopSeoSettings::isEnablePlugin() || !$is_valid_data)
		{
			return;
		}

		$state = json_decode(waRequest::post('shop_seo_state'), true);
		$product_id = $data['data']['id'];
		$groups = $state['groups'];

		foreach ($groups as $storefront_id => $_groups)
		{
			foreach (shopSeoProductGroup::getGroups($product_id, $storefront_id) as $group)
			{
				$settings = ifset($_groups['settings'][$group->getID()], array());
				$group->setSettings($settings);

				$templates = ifset($_groups['templates'][$group->getID()], array());
				$group->setTemplates($templates);
			}

			$fields_storage = new shopSeoProductFieldsStorage($storefront_id, $product_id);
			$fields_storage->setValues(ifset($_groups['fields_values'], array()));
		}
	}
}