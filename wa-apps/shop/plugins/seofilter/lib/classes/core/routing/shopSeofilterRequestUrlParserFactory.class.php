<?php

class shopSeofilterRequestUrlParserFactory
{
	public function getFrontendUrlParser($domain, array $route, $current_currency = null)
	{
		$currency = $current_currency
			? $current_currency
			: ifset($route, 'currency', 'RUB');

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$tree_settings = new shopSeofilterFilterTreeSettings();
		$filter_storage = new shopSeofilterFiltersStorage();

		return new shopSeofilterRequestUrlParser(
			$domain,
			$route,
			$currency,
			$settings,
			$tree_settings,
			$filter_storage
		);
	}
}