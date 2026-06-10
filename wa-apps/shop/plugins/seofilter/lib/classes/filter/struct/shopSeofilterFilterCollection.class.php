<?php

class shopSeofilterFilterCollection implements IteratorAggregate
{
	/** @var shopSeofilterFilter  */
	private static $filter = null;

	private $sql;
	private $params;
	private $offset;
	private $limit;

	private $total_count;

	public function __construct($select_sql_query, $params, $offset = 0, $limit = 0)
	{
		if (self::$filter === null)
		{
			self::$filter = new shopSeofilterFilter();
		}

		$model = self::$filter->model();

		$model->exec($select_sql_query, $params);
		$this->total_count = (int)$model->query('SELECT FOUND_ROWS()')->fetchField();

		$this->sql = str_replace('SQL_CALC_FOUND_ROWS', ' ', $select_sql_query);
		$this->params = $params;

		$this->offset = $offset;
		$this->limit = $limit;
	}

	public function count()
	{
		return $this->total_count;
	}

	public function iterator()
	{
		return new shopSeofilterFilterIterator($this);
	}

	public function loadChunk($offset, $chunk_size)
	{
		try
		{
			$model = self::$filter->model();
			return $model->query($this->sql . ' LIMIT ' . $offset . ', ' . $chunk_size, $this->params)->fetchAll();
		}
		catch (Exception $e)
		{
			return array();
		}
	}

	public function getOffset()
	{
		return $this->offset;
	}

	public function getLimit()
	{
		return $this->limit;
	}

	public function getIterator()
	{
		return $this->iterator();
	}

	private function createObject($row)
	{
		$filter = new shopSeofilterFilter();
		$filter->setAttributes($row);

		$filter->setIsNewRecord(false);

		return $filter;
	}
}
