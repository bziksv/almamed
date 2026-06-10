<?php

class shopSeofilterSitemapCacheQueueModel extends waModel
{
	const UNLOCK_THRESHOLD = 200;

	protected $table = 'shop_seofilter_sitemap_cache_queue';

	public function getFromTop($generator_process_id = null)
	{
		$params = array(
			'time_' => time(),
			'unlock_threshold' => time() - self::UNLOCK_THRESHOLD,
		);

		if (is_string($generator_process_id) && strlen($generator_process_id))
		{
			$queue_item = $this->select('`storefront`, `category_id`, `domain`, `shop_url`, `filter_ids`, `filter_ids_with_single_value`')
				->where('cache_generator_id = :generator_id', array('generator_id' => $generator_process_id))
				->where('(lock_timestamp IS NULL OR lock_timestamp < :unlock_threshold)', $params)
				->limit('1')
				->fetchAssoc();
		}
		else
		{
			$min_time = $this->select('MIN(`refresh_after`)')
				->where('refresh_after < :time_ AND (lock_timestamp IS NULL OR lock_timestamp < :unlock_threshold)', $params)
				->where('cache_generator_id IS NULL')
				->fetchField();

			if ($min_time === false)
			{
				return null;
			}

			$queue_item = $this->select('`storefront`, `category_id`, `domain`, `shop_url`, `filter_ids`, `filter_ids_with_single_value`')
				->where('refresh_after = :min_time', array('min_time' => $min_time))
				->where('(lock_timestamp IS NULL OR lock_timestamp < :unlock_threshold)', $params)
				->where('cache_generator_id IS NULL')
				->limit('1')
				->fetchAssoc();
		}

		if (!$queue_item)
		{
			return null;
		}

		$this->lock($queue_item);

		return $this->unserializeQueueItem($queue_item);
	}

	public function update($queue_item)
	{
		$storefront = ifset($queue_item['storefront']);
		$category_id = ifset($queue_item['category_id']);

		if ($storefront === null || $category_id === null)
		{
			throw new waException('invalid queue item');
		}

		if (!isset($queue_item['lock_timestamp']))
		{
			$queue_item['lock_timestamp'] = null;
		}

		if (!isset($queue_item['refresh_after']))
		{
			$queue_item['refresh_after'] = 2;
		}

		$this->insert($this->serializeQueueItem($queue_item), self::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function push($queue_item)
	{
		if (!isset($queue_item['refresh_after']) || !$queue_item['refresh_after'])
		{
			$max_refresh_after = $this->select('MAX(refresh_after)')->fetchField();

			if (!$max_refresh_after)
			{
				$max_refresh_after = time();
			}

			$refresh_after = time() + shopSeofilterSitemapCache::CACHE_TTL;
			if ($refresh_after < $max_refresh_after)
			{
				$refresh_after = $max_refresh_after + shopSeofilterSitemapCache::CACHE_UPDATE_MINIMUM_INTERVAL;
			}

			$queue_item['refresh_after'] = $refresh_after;
		}

		$this->update($queue_item);
	}

	public function prioritiseCategories($category_ids)
	{
		if (!count($category_ids))
		{
			return;
		}

		$min_refresh_after = $this->select('MIN(refresh_after)')->fetchField();

		if ($min_refresh_after === false)
		{
			return;
		}

		$params = array(
			'category_ids' => $category_ids,
			'min_refresh_after' => $min_refresh_after == 1 ? $min_refresh_after : $min_refresh_after - 1,
			'unlock_threshold' => time() - self::UNLOCK_THRESHOLD,
		);

		$sql = "
UPDATE {$this->table}
SET
	refresh_after = :min_refresh_after,
	lock_timestamp = NULL
WHERE
	category_id IN (:category_ids)
	AND (lock_timestamp IS NULL OR lock_timestamp > :unlock_threshold) 
";

		$this->exec($sql, $params);
	}

	private function lock($queue_item)
	{
		$pk = array(
			'storefront' => $queue_item['storefront'],
			'category_id' => $queue_item['category_id'],
		);

		$update = array(
			'lock_timestamp' => time(),
		);

		$this->updateByField($pk, $update);
	}

	private function serializeQueueItem($item)
	{
		if (!$item)
		{
			return null;
		}

		$filter_ids_fields = array(
			'filter_ids',
			'filter_ids_with_single_value',
		);

		foreach ($filter_ids_fields as $field)
		{
			if (isset($item[$field]))
			{
				$item[$field] = is_array($item[$field])
					? implode(',', array_values($item[$field]))
					: '';
			}
		}

		return $item;
	}

	private function unserializeQueueItem($item)
	{
		if (!$item)
		{
			return null;
		}

		$filter_ids_fields = array(
			'filter_ids',
			'filter_ids_with_single_value',
		);

		foreach ($filter_ids_fields as $field)
		{
			try
			{
				$filter_ids = explode(',', $item[$field]);

				$item[$field] = array();

				foreach ($filter_ids as $id)
				{
					$item[$field][$id] = $id;
				}
			}
			catch (Exception $e)
			{
				$item[$field] = array();
			}
		}

		return $item;
	}
}
