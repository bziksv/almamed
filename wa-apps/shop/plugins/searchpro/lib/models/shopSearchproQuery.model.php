<?php

class shopSearchproQueryModel extends waModel
{
	protected $table = 'shop_searchpro_query';

	public function save($query, $category_id, $count = null)
	{
		if($query === '') {
			return false;
		}

		$saved_data = $this->get($query, $category_id);
		$is_saved = $saved_data !== null;
		$datetime = date('Y-m-d H:i:s');

		if(!$is_saved) {
			return $this->insert(array(
				'query' => $query,
				'category_id' => $category_id,
				'first_datetime' => $datetime,
				'last_datetime' => $datetime,
				'count' => $count
			));
		}

		$id = $saved_data['id'];
		$frequency = (int) $saved_data['frequency'];
		$new_frequency = $frequency + 1;

		return $this->updateById($id, array(
			'frequency' => $new_frequency,
			'last_datetime' => $datetime,
			'count' => $count
		));
	}

	public function get($query, $category_id)
	{
		return $this->getByField(array(
			'query' => $query,
			'category_id' => $category_id
		));
	}

	public function getVisible($limit = null)
	{
		$limit = $limit !== null ? max(1, (int) $limit) : null;
		$cache_key = 'visible_' . ($limit !== null ? $limit : 'all');

		$cache = new waSerializeCache($cache_key, 600, 'shop/searchpro/popular');
		if ($cache->isCached()) {
			$cached = $cache->get();
			if (is_array($cached)) {
				return $cached;
			}
		}

		$rows = $this->fetchVisible($limit);
		$cache->set($rows);

		return $rows;
	}

	protected function fetchVisible($limit = null)
	{
		$sql = <<<SQL
SELECT q.*, c.name AS category_name
	FROM {$this->getTableName()} AS q
LEFT JOIN `shop_category` AS c
	ON c.id = q.category_id
WHERE q.status = '1' AND q.query != ''
ORDER BY q.frequency DESC
SQL;
		if ($limit !== null) {
			$limit = $this->escape($limit, 'int');
			$sql .= " LIMIT $limit";
		}

		return $this->query($sql)->fetchAll();
	}

	protected function getWhereSQL($type)
	{
		$where = '';
		if($type === 'empty') {
			$where .= " AND q.count = 0";
		}

		return $where;
	}

	public function getCount($type = 'all')
	{
		$where = $this->getWhereSQL($type);

		return $this->query("SELECT COUNT(*) FROM {$this->getTableName()} AS q WHERE 1" . $where)->fetchField();
	}

	public function getQueries($offset = null, $limit = null, $sort = null, $order = null, $type = 'all')
	{
		if($sort === null || $order === null) {
			$order_by = 'last_datetime DESC, frequency DESC';
		} else {
			$order_by = $this->escape($order) . ' ' . $this->escape($sort);
		}

		$where = $this->getWhereSQL($type);

		$sql = <<<SQL
SELECT q.*, c.name AS category_name
	FROM {$this->getTableName()} AS q
LEFT JOIN `shop_category` AS c
	ON c.id = q.category_id
WHERE 1{$where}
ORDER BY
	{$order_by}
SQL;

		if($limit !== null) {
			$limit = $this->escape($limit, 'int');
			$sql .= " LIMIT ";

			if($offset !== null) {
				$offset = $this->escape($offset, 'int');
				$sql .= "{$offset}, ";
			}

			$sql .= $limit;
		}

		return $this->query($sql)->fetchAll();
	}

	public function countCleanupCandidates($top_limit = 10000, $keep_days = 90)
	{
		$top_limit = max(1, (int) $top_limit);
		$keep_days = max(1, (int) $keep_days);
		$cutoff = date('Y-m-d H:i:s', strtotime('-' . $keep_days . ' days'));

		$sql = <<<SQL
SELECT COUNT(*)
FROM {$this->getTableName()} AS q
WHERE q.last_datetime < s:cutoff
AND q.id NOT IN (
	SELECT id FROM (
		SELECT id
		FROM {$this->getTableName()}
		ORDER BY frequency DESC
		LIMIT i:top_limit
	) AS kept
)
SQL;

		return (int) $this->query($sql, array(
			'cutoff' => $cutoff,
			'top_limit' => $top_limit,
		))->fetchField();
	}

	public function cleanup($top_limit = 10000, $keep_days = 90)
	{
		$top_limit = max(1, (int) $top_limit);
		$keep_days = max(1, (int) $keep_days);
		$cutoff = date('Y-m-d H:i:s', strtotime('-' . $keep_days . ' days'));

		$sql = <<<SQL
DELETE FROM {$this->getTableName()}
WHERE last_datetime < s:cutoff
AND id NOT IN (
	SELECT id FROM (
		SELECT id
		FROM {$this->getTableName()}
		ORDER BY frequency DESC
		LIMIT i:top_limit
	) AS kept
)
SQL;

		$result = $this->exec($sql, array(
			'cutoff' => $cutoff,
			'top_limit' => $top_limit,
		));

		$this->clearPopularCache();

		if ($result instanceof waDbResultDelete) {
			return (int) $result->affectedRows();
		}

		return 0;
	}

	public function clearPopularCache()
	{
		foreach (array(3, 5, 10, 15, 20, 30, 50, 'all') as $limit) {
			$cache = new waSerializeCache('visible_' . $limit, 600, 'shop/searchpro/popular');
			if ($cache->isCached()) {
				$cache->delete();
			}
		}
	}
}
