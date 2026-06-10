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
		$sql = <<<SQL
SELECT *
	FROM {$this->getTableName()}
WHERE status = '1' AND query != ''
ORDER BY frequency DESC
SQL;
		if($limit !== null) {
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
}
