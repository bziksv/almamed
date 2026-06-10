<?php

class shopSeofilterSitemapCacheModel extends waModel
{
	const UNLOCK_THRESHOLD = 200;

	protected $table = 'shop_seofilter_sitemap_cache';

	/**
	 * @param string $storefront
	 * @param bool $in_stock_only
	 * @param bool $categories_with_filter_only
	 * @return waDbResultSelect
	 */
	public function getByStorefrontQuery($storefront, $in_stock_only, $categories_with_filter_only)
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();

		$filter_ids_select = '`filter_ids`';
		$lastmod_select = '`lastmod`';
		$storefront_join = '';

		if (!$settings->cache_for_single_storefront)
		{
			$filter_ids_select = 'COALESCE(`t2`.`filter_ids`, `t1`.`filter_ids`)';
			$lastmod_select = 'COALESCE(`t2`.`lastmod`, `t1`.`lastmod`)';

			$storefront_join = '
	LEFT JOIN (
		SELECT `t`.storefront, `t`.`category_id`, `t`.`lastmod`, `t`.`filter_ids`
		FROM shop_seofilter_sitemap_cache AS t
		WHERE `t`.`storefront` = :storefront
	) AS t2
		ON t1.category_id = t2.category_id
';
		}


		$sql = "
SELECT DISTINCT
	`t1`.`category_id` AS category_id,
	{$filter_ids_select} AS filter_ids,
	{$lastmod_select} AS lastmod
FROM shop_seofilter_sitemap_cache AS t1
	{$storefront_join}";

		if ($categories_with_filter_only && !shopSeofilterHelper::isSmartfiltersPluginEnabled())
		{
			$sql .= '
	JOIN `shop_category` AS `category`
		ON `category`.`id` = `t1`.`category_id`
';
		}

		if (!$settings->cache_for_single_storefront)
		{
			$sql .= '
WHERE `t1`.`storefront` IN (:storefront, :default_storefront)
	AND COALESCE(`t2`.`filter_ids`, `t1`.`filter_ids`) <> \'\'
';
		}

		if ($categories_with_filter_only && !shopSeofilterHelper::isSmartfiltersPluginEnabled())
		{
			$sql .= 'AND `category`.`filter` IS NOT NULL AND `category`.`filter` <> \'\'';
		}

//		$sql .='
//GROUP BY t1.category_id
//';
		// todo понять зачем group by

		$default_storefront = $settings->getDefaultSitemapCacheStorefront($in_stock_only);

		return $this->query($sql, array(
			'storefront' => $storefront,
			'default_storefront' => $default_storefront,
		));
	}

	public function save($data)
	{
		$data['filter_ids'] = isset($data['filter_ids']) && is_array($data['filter_ids'])
			? implode(',', $data['filter_ids'])
			: '';

		$this->insert($data, self::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function update($cache_updates)
	{
		foreach ($cache_updates as $cache_update)
		{
			$this->updateOne($cache_update);
		}
	}

	private function updateOne($cache_update)
	{
		$pk = array(
			'storefront' => $cache_update['storefront'],
			'category_id' => $cache_update['category_id'],
		);

		$cached_filters_ids = array();
		$row = $this->getByField($pk);

		if ($row && $row['filter_ids'] !== '')
		{
			foreach (explode(',', $row['filter_ids']) as $filter_id)
			{
				$cached_filters_ids[$filter_id] = (int)$filter_id;
			}

			foreach ($cache_update['invalid_filter_ids'] as $filter_id)
			{
				unset($cached_filters_ids[$filter_id]);
			}
		}

		foreach ($cache_update['valid_filter_ids'] as $filter_id)
		{
			$cached_filters_ids[$filter_id] = (int)$filter_id;
		}

		$this->insert(array(
			'storefront' => $cache_update['storefront'],
			'category_id' => $cache_update['category_id'],
			'filter_ids' => count($cached_filters_ids) ? implode(',', array_values($cached_filters_ids)) : '',
			'lastmod' => date('Y-m-d H:i:s'),
		), self::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}
}
