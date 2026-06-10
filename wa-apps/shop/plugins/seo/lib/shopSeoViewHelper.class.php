<?php

class shopSeoViewHelper
{
	public static function extendCategory($category, $storefront = null)
	{
		if (!isset($storefront))
		{
			$storefront = self::getCurrentStorefront();
		}
		
		$category_extender = shopSeoContext::getInstance()->getCategoryExtender();
		
		return $category_extender->extend($storefront, $category, waRequest::get('page', 1));
	}
	
	public static function extendProduct($product, $storefront = null)
	{
		if (!isset($storefront))
		{
			$storefront = self::getCurrentStorefront();
		}
		
		$product_extender = shopSeoContext::getInstance()->getProductExtender();
		
		return $product_extender->extend($storefront, $product);
	}

	/**
	 * Batch seo_name for product lists (category, search, home blocks).
	 * Replaces per-item extendProduct() in list-thumbs — one SQL query instead of N× heavy collect().
	 *
	 * @param int[] $product_ids
	 * @return array [product_id => seo_name]
	 */
	public static function getProductSeoNamesByIds($product_ids)
	{
		if (!$product_ids)
		{
			return array();
		}

		$product_ids = array_map('intval', array_values($product_ids));
		$context = shopSeoContext::getInstance();
		$storefront = $context->getStorefrontService()->getCurrentStorefront();
		$groups_storefronts = $context->getGroupStorefrontService()->getByStorefront($storefront);

		$model = new shopSeoProductSettingsModel();
		$rows = $model->query(
			'SELECT product_id, group_storefront_id, value
			 FROM shop_seo_product_settings
			 WHERE product_id IN (i:ids) AND name = \'seo_name\'',
			array('ids' => $product_ids)
		)->fetchAll();

		$settings_by_product = array();
		foreach ($rows as $row)
		{
			$settings_by_product[$row['product_id']][$row['group_storefront_id']] = $row['value'];
		}

		$names = array();
		foreach ($product_ids as $product_id)
		{
			$collection = new shopSeoLayoutsCollection(array('seo_name'));
			$settings = isset($settings_by_product[$product_id]) ? $settings_by_product[$product_id] : array();

			foreach ($groups_storefronts as $group_storefront)
			{
				$group_id = $group_storefront->getId();
				$value = isset($settings[$group_id]) ? $settings[$group_id] : '';
				$collection->push(array(
					'seo_name' => $value,
				), 1, 'personal; storefront group');
			}

			$general = isset($settings[0]) ? $settings[0] : '';
			$collection->push(array(
				'seo_name' => $general,
			), 1, 'personal; general');

			$seo_name = $collection->getResult();
			if ($seo_name['seo_name'] !== '')
			{
				$names[$product_id] = $seo_name['seo_name'];
			}
		}

		return $names;
	}

	/**
	 * Batch seo_name for category lists (subcategory grid on category page).
	 *
	 * @param int[] $category_ids
	 * @return array [category_id => seo_name]
	 */
	public static function getCategorySeoNamesByIds($category_ids)
	{
		if (!$category_ids)
		{
			return array();
		}

		$category_ids = array_map('intval', array_values($category_ids));
		$context = shopSeoContext::getInstance();
		$storefront = $context->getStorefrontService()->getCurrentStorefront();
		$groups_storefronts = $context->getGroupStorefrontService()->getByStorefront($storefront);

		$model = new shopSeoCategorySettingsModel();
		$rows = $model->query(
			'SELECT category_id, group_storefront_id, value
			 FROM shop_seo_category_settings
			 WHERE category_id IN (i:ids) AND name = \'seo_name\'',
			array('ids' => $category_ids)
		)->fetchAll();

		$settings_by_category = array();
		foreach ($rows as $row)
		{
			$settings_by_category[$row['category_id']][$row['group_storefront_id']] = $row['value'];
		}

		$names = array();
		foreach ($category_ids as $category_id)
		{
			$collection = new shopSeoLayoutsCollection(array('seo_name'));
			$settings = isset($settings_by_category[$category_id]) ? $settings_by_category[$category_id] : array();

			foreach ($groups_storefronts as $group_storefront)
			{
				$group_id = $group_storefront->getId();
				$value = isset($settings[$group_id]) ? $settings[$group_id] : '';
				$collection->push(array(
					'seo_name' => $value,
				), 1, 'personal; storefront group');
			}

			$general = isset($settings[0]) ? $settings[0] : '';
			$collection->push(array(
				'seo_name' => $general,
			), 1, 'personal; general');

			$seo_name = $collection->getResult();
			if ($seo_name['seo_name'] !== '')
			{
				$names[$category_id] = $seo_name['seo_name'];
			}
		}

		return $names;
	}
	
	public static function getContext()
	{
		return shopSeoContext::getInstance();
	}
	
	private static function getCurrentStorefront()
	{
		$storefront_service = shopSeoContext::getInstance()->getStorefrontService();
		
		return $storefront_service->getCurrentStorefront();
	}
}