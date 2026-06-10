<?php

class shopSeoPluginHiddenSettingsSource
{
	public function getSettings()
	{
		$settings_assoc = $this->getDefaultSettingsAssoc();

		$settings_path = wa('shop')->getConfigPath('shop/plugins/seo') . '/config.php';
		if (file_exists($settings_path))
		{
			$hidden_config = include($settings_path);

			if (is_array($hidden_config))
			{
				foreach (array_keys($settings_assoc) as $name)
				{
					if (array_key_exists($name, $hidden_config))
					{
						$settings_assoc[$name] = $hidden_config[$name];
					}
				}
			}
		}

		return $settings_assoc;
	}

	private function getDefaultSettingsAssoc()
	{
		return array(
			'cache_for_single_storefront' => false,
		);
	}
}
