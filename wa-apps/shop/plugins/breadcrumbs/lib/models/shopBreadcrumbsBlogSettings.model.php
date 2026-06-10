<?php

class shopBreadcrumbsBlogSettingsModel extends waModel
{
	const DB_TRUE = '1';
	const DB_FALSE = '0';

	protected $table = 'shop_breadcrumbs_blog_settings';

	public function getSettings()
	{
		return $this
			->select('name,value')
			->fetchAll('name', true);
	}

	public function set($option, $value)
	{
		$data = array(
			'name' => $option,
			'value' => $value,
		);

		$this->insert($data, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function availableSettings()
	{
		return array(
			'root_element_app' => 'root_element_app',
			'current_level_mode' => 'current_level_mode',
		);
	}
}