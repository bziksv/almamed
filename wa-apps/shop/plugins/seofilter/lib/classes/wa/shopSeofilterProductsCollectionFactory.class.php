<?php

class shopSeofilterProductsCollectionFactory
{
	public static function getCollection($hash = '', $options = array())
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();

		return $settings->use_custom_products_collection
			? new shopSeofilterProductsCollectionFixed($hash, $options)
			: new shopSeofilterProductsCollection($hash, $options);
	}
}