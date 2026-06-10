<?php

class shopSeofilterPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$plugin = wa('shop')->getPlugin('seofilter');
		$data = array();

		$data['storefronts'] = $this->getStorefronts();
		$data['url_types'] = $this->getUrlTypes();
		$data['basic_settings'] = $this->getBasicSettings();
		$data['storefront_fields'] = array(
			'fields' => shopSeofilterStorefrontFieldsModel::getAllFields(),
		);
		$data['filter_fields'] = $this->getFilterFields();
		$data['template_rules'] = array(
			'current_storefront' => '*',
			'data' => array(
				'*' => array(
					'templates' => $this->getTemplateRules('*'),
					'settings' => shopSeofilterDefaultTemplateSettingsModel::getSettings('*')->getRawSettings(),
				),
			),
		);

		$data['custom_template_variables_meta'] = $this->getCustomTemplateVariablesMeta();

		$template_rule_model = new shopSeofilterDefaultTemplateModel();
		$data['modified_storefronts'] = array_keys($template_rule_model->select('DISTINCT storefront')->where('value != \'\'')->fetchAll('storefront'));
		$data['is_productbrands_plugin_installed'] = $this->isProductbrandsPluginInstalled();
		$data['productfilters_state'] = $this->getProductfiltersState();
		$data['server_env_user'] = $this->getServerUser();
		$data['_csrf'] = waRequest::cookie('_csrf');

		$this->view->assign(
			array(
				'plugin_url' => $plugin->getPluginStaticUrl(),
				'plugin_version' => $plugin->getVersion(),
				'data' => $data,
				'cli_path' => wa()->getConfig()->getRootPath() . DIRECTORY_SEPARATOR . 'cli.php',
				'sitemap_cron_is_installed' => $this->cronIsInstalled(),
				'wa_plugin_version' => $plugin->getVersion(),
			)
		);
	}

	protected function isProductbrandsPluginInstalled()
	{
		return wa('shop')->getConfig()->getPluginInfo('productbrands') !== array();
	}

	protected function getStorefronts()
	{
		$storefronts = array('*');
		$storefront_model = new shopSeofilterStorefrontModel();
		$storefronts = array_merge($storefronts, $storefront_model->getStorefronts());

		return $storefronts;
	}

	protected function getTemplateRules($storefront)
	{
		$template_rule_model = new shopSeofilterDefaultTemplateModel();
		$template_rules = $template_rule_model->getAsArray($storefront);

		return $template_rules;
	}

	protected function getBasicSettings()
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$basic_settings = $settings->getRawSettings();

		$basic_settings['yandex_metric']['current_storefront'] = '*';
		if (!ifset($basic_settings['yandex_metric']['codes']))
		{
			$basic_settings['yandex_metric']['codes'] = array('*' => '');
		}

		return $basic_settings;
	}

	/**
	 * @return array
	 */
	protected function getUrlTypes()
	{
		$url_types = array();
		foreach (shopSeofilterFilterUrl::getUrlTypes() as $type => $title)
		{
			$url_types[] = array(
				'value' => $type,
				'title' => $title,
			);
		}

		return $url_types;
	}

	private function getCustomTemplateVariablesMeta()
	{
		$meta = new shopSeofilterCustomTemplateVariablesMeta();

		return $meta->getCustomTemplateVariablesMeta();
	}

	public function getProductfiltersState()
	{
		$settings_action = new shopSeofilterPluginProductfiltersSettingsAction();

		return $settings_action->getState();
	}

	/**
	 * @return array
	 */
	public function getFilterFields()
	{
		$all_fields = shopSeofilterFilterFieldModel::getAllFields();

		return array(
			'fields' => $all_fields,
			'new_id' => count($all_fields) ? max(array_keys($all_fields)) : 1,
		);
	}

	private function cronIsInstalled()
	{
		$settings_model = new waAppSettingsModel();

		$last_run_timestamp = $settings_model->get('shop.seofilter', 'cron_is_installed', '0');

		return $last_run_timestamp === '1' || intval($last_run_timestamp) + 86400 > time();
	}

	/**
	 * @return string|null
	 */
	protected function getServerUser()
	{
		if (is_string(PHP_OS) && trim(strtolower(PHP_OS)) === 'linux')
		{
			$server_user = @shell_exec('whoami');
			if (is_string($server_user) && trim($server_user) !== '')
			{
				return trim($server_user);
			}
		}

		return null;
	}
}
