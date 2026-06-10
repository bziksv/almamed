<?php

class shopSeofilterSitemapCache
{
	const CACHE_TTL = 86400;
	const CACHE_TTL_CRON = 172800;
	const CACHE_UPDATE_MINIMUM_INTERVAL = 10;
	const CACHE_CHECK_INTERVAL = 604800;
	const STEP_EXECUTION_TIME_FRONTEND_THRESHOLD = 0.2;


	private static $shutdown_callback_initialised = false;
	private static $category_ids = array();
	private static $deleted_category_ids = array();

	private $plugin_settings = null;

	/** @var shopSeofilterSitemapCacheModel */
	private $sitemap_cache_model = null;
	/** @var shopSeofilterSitemapCacheQueueModel */
	private $sitemap_cache_queue_model = null;

	private $generator_process_id = null;
	private $step_execution_time_threshold = null;

	public function __construct($generator_process_id = null, $step_execution_time_threshold = null)
	{
		if (!self::$shutdown_callback_initialised && $generator_process_id)
		{
			self::$shutdown_callback_initialised = true;
		}

		$this->plugin_settings = shopSeofilterBasicSettingsModel::getSettings();

		$this->generator_process_id = $generator_process_id;
		$this->step_execution_time_threshold = is_numeric($step_execution_time_threshold)
			? (float)$step_execution_time_threshold
			: self::STEP_EXECUTION_TIME_FRONTEND_THRESHOLD;

		$this->sitemap_cache_model = new shopSeofilterSitemapCacheModel();
		$this->sitemap_cache_queue_model = new shopSeofilterSitemapCacheQueueModel();
	}

	public function step()
	{
		$sitemap_cache_check_after = $this->plugin_settings->sitemap_cache_check_after;

		$this->checkDefaultSitemapStorefronts();

		if (!$this->generator_process_id && (!$sitemap_cache_check_after || $sitemap_cache_check_after < time()))
		{
			$this->buildQueue();
		}
		else
		{
			$this->serveQueueHead();
		}
	}

	public function invalidateForCategories($category_ids)
	{
		$this->tryRegisterOnShutdownCallback();

		foreach ($category_ids as $category_id)
		{
			self::$category_ids[$category_id] = 1;
		}
	}

	public function invalidateByProductId($product_id)
	{
		$category_products_model = new shopCategoryProductsModel();

		$category_ids = array();
		if (is_array($product_id))
		{
			if (!count($product_id))
			{
				return;
			}

			$products_model = new shopProductModel();

			$sql = '
SELECT DISTINCT cp.category_id `category_id`
FROM `' . $category_products_model->getTableName() . '` cp
JOIN `' . $products_model->getTableName() . '` p ON cp.product_id = p.id
WHERE p.id IN (s:ids) AND p.`status` = 1
';

			$result = $products_model->query($sql, array('ids' => $product_id))->fetchAll('category_id');
			$category_ids = array_keys($result);
		}
		elseif (wa_is_int($product_id))
		{
			$result = $category_products_model
				->select('category_id')
				->where('product_id = :product_id', array('product_id' => $product_id))
				->fetchAll('category_id');

			$category_ids = array_keys($result);
		}

		if (count($category_ids))
		{
			$sitemap_cache = new shopSeofilterSitemapCache();
			$sitemap_cache->invalidateForCategories($category_ids);
		}
	}

	public function removeForCategory($category)
	{
		$category_model = new shopCategoryModel();

		$categories = $category_model
			->descendants($category, true)
			->select('id')
			->fetchAll('id');

		if (count($categories))
		{
			$this->tryRegisterOnShutdownCallback();

			self::$deleted_category_ids = $categories;
		}
	}



	public function onShutdown()
	{
		$this->_clearForDeletedCategories();
		$this->_invalidateForCategories();
	}

	private function _invalidateForCategories()
	{
		$category_model = new shopCategoryModel();

		$category_ids = array();
		foreach (self::$category_ids as $category_id => $_)
		{
			if (isset($category_ids[$category_id]))
			{
				continue;
			}

			$category_ids[$category_id] = 1;

			$categories = $category_model
				->descendants($category_id, false)
				->select('id')
				->fetchAll('id');

			foreach ($categories as $_category_id => $__)
			{
				$category_ids[$_category_id] = 1;
			}
		}

		if (!count($category_ids))
		{
			self::$category_ids = array();
			return;
		}

		$this->sitemap_cache_queue_model->prioritiseCategories(array_keys($category_ids));

		$settings_model = new shopSeofilterBasicSettingsModel();
		$settings_model->set('sitemap_cache_check_after', time() + 15 * 60);

		self::$category_ids = array();
	}

	private function _clearForDeletedCategories()
	{
		if (count(self::$deleted_category_ids))
		{
			$category_ids = array_keys(self::$deleted_category_ids);

			$this->sitemap_cache_model->deleteByField('category_id', $category_ids);
			$this->sitemap_cache_queue_model->deleteByField('category_id', $category_ids);

			self::$deleted_category_ids = array();
		}
	}



	private function serveQueueHead()
	{
		$elapsed_time = 0;

		$cache_updates = array();

		while ($queue_item = $this->sitemap_cache_queue_model->getFromTop($this->generator_process_id))
		{
			if (!($cache_generator = $this->tryGetCacheGenerator($queue_item)))
			{
				$delete_params = array(
					'storefront' => $queue_item['storefront'],
					'category_id' => $queue_item['category_id'],
				);

				if ($this->plugin_settings->cache_for_single_storefront)
				{
					unset($delete_params['storefront']);
				}

				$delete_result = $this->sitemap_cache_queue_model->deleteByField($delete_params);

				if (!$delete_result)
				{
					break;
				}

				continue;
			}

			$elapsed_time = $this->runCacheGeneratorLoop($cache_generator, $elapsed_time);
			$elapsed_time += $this->updateQueue($cache_generator);

			$cache_updates[] = $cache_generator->getCacheData();

			if ($elapsed_time > $this->step_execution_time_threshold)
			{
				break;
			}
		}

		$this->sitemap_cache_model->update($cache_updates);
	}

	private function tryRegisterOnShutdownCallback()
	{
		if (!$this->generator_process_id && !self::$shutdown_callback_initialised)
		{
			register_shutdown_function(array($this, 'onShutdown'));
			self::$shutdown_callback_initialised = true;
		}
	}

	/**
	 * @param $queue_item
	 * @return null|shopSeofilterSitemapCacheGenerator
	 */
	private function tryGetCacheGenerator($queue_item)
	{
		try
		{
			$cache_generator = new shopSeofilterSitemapCacheGenerator($queue_item);
			if ($this->plugin_settings->consider_category_filters)
			{
				$cache_generator->keepCategoryFilterFiltersOnly();
			}

			//if ($this->plugin_settings->cache_for_single_storefront)
			//{
			//	$cache_generator->ignoreStorefronts();
			//}

			return $cache_generator;
		}
		catch (waException $e)
		{
			return null;
		}
	}

	private function runCacheGeneratorLoop(shopSeofilterSitemapCacheGenerator $cache_generator, $elapsed_time)
	{
		while ($cache_generator->haveFiltersToCheck())
		{
			if ($elapsed_time > $this->step_execution_time_threshold)
			{
				break;
			}

			$time = $cache_generator->step();

			$elapsed_time += $time;
		}

		return $elapsed_time;
	}

	private function updateQueue(shopSeofilterSitemapCacheGenerator $cache_generator)
	{
		$start_at = microtime(true);

		$queue_item = $cache_generator->getQueueItem();

		if ($cache_generator->haveFiltersToCheck())
		{
			$queue_item['filter_ids'] = $cache_generator->getRemainingFilterIds();
			$queue_item['filter_ids_with_single_value'] = array();

			$this->sitemap_cache_queue_model->update($queue_item);
		}
		else
		{
			$this->regenerateCacheQueueItem($queue_item);
		}

		return microtime(true) - $start_at;
	}

	public function buildQueue($for_category_ids_only = array())
	{
		$model = new shopSeofilterBasicSettingsModel();
		$model->set('sitemap_cache_check_after', time() + self::CACHE_CHECK_INTERVAL);

		$model = new waModel();

		$all_categories = array();

		$sql = "
SELECT
	c.id AS id,
	GROUP_CONCAT(r.route SEPARATOR ',') AS storefronts
FROM shop_category AS c
	LEFT JOIN shop_category_routes AS r
		ON c.id = r.category_id
";
		$params = array();

		if (is_array($for_category_ids_only) && count($for_category_ids_only))
		{
			$sql .= 'WHERE c.id IN (s:category_ids)' . PHP_EOL;

			$params['category_ids'] = $for_category_ids_only;
		}

		$sql .= 'GROUP BY c.id';

		foreach ($model->query($sql, $params) as $category)
		{
			$category_id = $category['id'];

			$all_categories[$category_id] = $category;

			if ($category['storefronts'])
			{
				$all_categories[$category_id]['all_storefronts'] = false;
				$all_categories[$category_id]['storefronts'] = array();

				foreach (explode(',', $category['storefronts']) as $storefront)
				{
					$all_categories[$category_id]['storefronts'][$storefront] = $storefront;
				}
			}
			else
			{
				$all_categories[$category_id]['all_storefronts'] = true;
			}
		}

		$filter_storage = new shopSeofilterFiltersStorage();

		if (!is_array($for_category_ids_only) || !count($for_category_ids_only))
		{
			$this->sitemap_cache_queue_model->deleteByField(array(
				'lock_timestamp' => null,
			));
		}

		$refresh_after = time();

		$all_single_value_filter_ids = $filter_storage->getAllFilterIdsWithSingleValue();

		$all_storefronts = $this->getAllStorefronts();

		foreach ($all_storefronts as $storefront_params)
		{
			foreach ($all_categories as $category)
			{
				$storefront = $storefront_params['storefront'];

				$queue_item = array(
					'storefront' => $storefront,
					'category_id' => $category['id'],
					'filter_ids' => array(),
					'filter_ids_with_single_value' => array(),
					'refresh_after' => $refresh_after,
					'domain' => $storefront_params['domain'],
					'shop_url' => $storefront_params['route']['url'],
					'cache_generator_id' => $this->generator_process_id,
				);

				if ($category['all_storefronts'] || isset($category['storefronts'][$storefront]))
				{
					$all_filter_ids = $filter_storage->getAllFilterIdsForCategory(
						$storefront,
						$category['id']
					);

					foreach ($all_filter_ids as $filter_id)
					{
						if (isset($all_single_value_filter_ids[$filter_id]))
						{
							$queue_item['filter_ids_with_single_value'][] = $filter_id;
						}
						else
						{
							$queue_item['filter_ids'][] = $filter_id;
						}
					}

					$this->sitemap_cache_queue_model->push($queue_item);

					$refresh_after += self::CACHE_UPDATE_MINIMUM_INTERVAL;
				}
			}

			if ($this->plugin_settings->cache_for_single_storefront)
			{
				return;
			}
		}
	}

	private function getAllStorefronts()
	{
		return shopSeofilterStorefrontModel::getAllStorefrontParams();

		//if ($this->plugin_settings->cache_for_single_storefront)
		//{
		//	yield [
		//		'storefront' => '*',
		//		'domain' => '*',
		//		'route' => [
		//			'app' => 'shop',
		//			'url' => '*',
		//			'drop_out_of_stock' => '1',
		//		],
		//	];
		//
		//	return;
		//}

		//static $domains = null;
		//
		//if (!is_array($domains))
		//{
		//	$domains = wa()->getRouting()->getByApp('shop');
		//}
		//
		//foreach ($domains as $domain => $domain_routes)
		//{
		//	foreach ($domain_routes as $route)
		//	{
		//		yield [
		//			'storefront' => $domain . '/' . $route['url'],
		//			'domain' => $domain,
		//			'route' => $route,
		//		];
		//
		//		if ($this->plugin_settings->cache_for_single_storefront)
		//		{
		//			return;
		//		}
		//	}
		//}
		//
		//return;
	}

	/**
	 * @param $queue_item
	 */
	private function regenerateCacheQueueItem($queue_item)
	{
		$filter_storage = new shopSeofilterFiltersStorage();

		$all_single_value_filter_ids = $filter_storage->getAllFilterIdsWithSingleValue();

		$storefront = $queue_item['storefront'];
		$category_id = $queue_item['category_id'];

		$all_filter_ids = $filter_storage->getAllFilterIdsForCategory($storefront, $category_id);

		$queue_item['filter_ids'] = array();
		$queue_item['filter_ids_with_single_value'] = array();

		$queue_item_all_filter_ids = array();

		foreach ($all_filter_ids as $filter_id)
		{
			if (isset($all_single_value_filter_ids[$filter_id]))
			{
				$queue_item['filter_ids_with_single_value'][] = $filter_id;
			}
			else
			{
				$queue_item['filter_ids'][] = $filter_id;
			}

			$queue_item_all_filter_ids[$filter_id] = $filter_id;
		}

		unset($queue_item['refresh_after']);

		$queue_item['cache_generator_id'] = null;

		$this->removeUnavailableFilterIdsFromSitemapCache($storefront, $category_id, $queue_item_all_filter_ids);

		$this->sitemap_cache_queue_model->push($queue_item);
	}

	/**
	 * @param $storefront
	 * @param $category_id
	 * @param $queue_item_possible_filter_ids
	 */
	private function removeUnavailableFilterIdsFromSitemapCache($storefront, $category_id, $queue_item_possible_filter_ids)
	{
		$cache_item_pk = array(
			'storefront' => $storefront,
			'category_id' => $category_id,
		);
		$cache_item = $this->sitemap_cache_model->getByField($cache_item_pk);

		if ($cache_item)
		{
			$tmp = $cache_item['filter_ids'] == '' ? array() : explode(',', $cache_item['filter_ids']);

			$cache_filter_ids = array();
			foreach ($tmp as $id)
			{
				$cache_filter_ids[$id] = $id;
			}

			if (count($cache_filter_ids))
			{
				foreach (array_keys($cache_filter_ids) as $filter_id)
				{
					if (!array_key_exists($filter_id, $queue_item_possible_filter_ids))
					{
						unset($cache_filter_ids[$filter_id]);
					}
				}

				$cache_item_update = array(
					'filter_ids' => implode(',', $cache_filter_ids),
				);
				$this->sitemap_cache_model->updateByField($cache_item_pk, $cache_item_update);
			}
		}
	}

	private function checkDefaultSitemapStorefronts()
	{
		$settings = $this->plugin_settings;

		if (!$settings->sitemap_cache_default_storefront_hide_products || !$settings->sitemap_cache_default_storefront_show_products)
		{
			$drop_out_of_stock_storefronts = array();
			$dont_drop_out_of_stock_storefronts = array();

			foreach ($this->getAllStorefronts() as $storefront_params)
			{
				$storefront = $storefront_params['storefront'];

				if (array_key_exists('drop_out_of_stock', $storefront_params['route']))
				{
					if ($storefront_params['route']['drop_out_of_stock'] == '2')
					{
						$drop_out_of_stock_storefronts[$storefront] = $storefront;
					}
					else
					{
						$dont_drop_out_of_stock_storefronts[$storefront] = $storefront;
					}
				}
			}

			$sql = '
SELECT t.storefront
FROM shop_seofilter_sitemap_cache t
WHERE t.storefront IN (s:storefronts)
GROUP BY t.storefront
HAVING COUNT(t.storefront) > 0
ORDER BY COUNT(t.storefront) DESC
LIMIT 1
';

			$settings_model = new shopSeofilterBasicSettingsModel();

			if (count($drop_out_of_stock_storefronts) && !$settings->sitemap_cache_default_storefront_hide_products)
			{
				$storefront = $this->sitemap_cache_model
					->query($sql, array('storefronts' => $drop_out_of_stock_storefronts))
					->fetchField();

				if ($storefront)
				{
					$settings_model->setDefaultSitemapCacheStorefront($storefront, true);
				}
			}

			if (count($dont_drop_out_of_stock_storefronts) && !$settings->sitemap_cache_default_storefront_show_products)
			{
				$storefront = $this->sitemap_cache_model
					->query($sql, array('storefronts' => $dont_drop_out_of_stock_storefronts))
					->fetchField();

				if ($storefront)
				{
					$settings_model->setDefaultSitemapCacheStorefront($storefront, false);
				}
			}
		}
	}
}
