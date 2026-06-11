<?php

class shopSearchproV2Settings
{
	private $env;

	public function __construct(shopSearchproEnv $env = null)
	{
		$this->env = $env !== null ? $env : shopSearchproPlugin::getEnv();
	}

	public static function create()
	{
		return new self();
	}

	public static function isUseV2()
	{
		$flag = shopSearchproPlugin::staticallyGetSettings('use_v2');
		return $flag === null || $flag === '' || (bool) $flag;
	}

	public function get($name, $default = null)
	{
		$value = shopSearchproPlugin::staticallyGetSettings($name);
		return $value !== null && $value !== '' ? $value : $default;
	}

	public function getEnv()
	{
		return $this->env;
	}

	public function isEnabled()
	{
		return (bool) $this->get('status');
	}

	public function getDropdownCacheTtl()
	{
		return (int) $this->get('dropdown_results_cache', 10800);
	}

	public function getPageCacheTtl()
	{
		return (int) $this->get('page_results_cache', 0);
	}

	public function getEntitiesSort()
	{
		$sort = $this->get('dropdown_entities_sort', array());
		return is_array($sort) ? $sort : array();
	}
}
