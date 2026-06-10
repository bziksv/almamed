<?php


class shopSeoWaFetchTemplateHelperEvent
{
	public function getVars($type)
	{
		switch ($type)
		{
			case shopSeoGroupTypes::MAIN:
				$types = shopSeoMainGroupTypes::getAll();
				break;
			case shopSeoGroupTypes::CATEGORY:
				$types = shopSeoCategoryGroupTypes::getAll();
				break;
			case shopSeoGroupTypes::PRODUCT:
				$types = shopSeoProductGroupTypes::getAll();
				break;
			default:
				$types = array();
		}

		$custom_variables = array();

		foreach ($types as $_type)
		{
			if (!isset($custom_variables[$_type]))
			{
				$custom_variables[$_type] = array();
			}

			$params_type = array(
				'type' => 'main',
				'group_type' => $_type
			);
			$variables = wa('shop')->event(array('shop', 'seo_fetch_template_helper'), $params_type);

			foreach ($variables as $app_id => $_variables)
			{
				if (preg_match('/^(.*)\-plugin$/', $app_id, $matches))
				{
					$plugin_id = $matches[1];
					$name = wa('shop')->getPlugin($plugin_id)->getName();
				}
				else
				{
					$app_info = wa()->getAppInfo($app_id);
					$name = $app_info['name'];
				}

				$custom_variables[$_type][$name] = $variables[$app_id];
			}
		}

		return $custom_variables;
	}
}