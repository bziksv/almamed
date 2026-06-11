<?php

class shopSearchproV2SuggestResult
{
	public $query;
	public $category_id;
	public $results;
	public $count;

	public function __construct($query, $category_id, array $results, $count)
	{
		$this->query = $query;
		$this->category_id = (int) $category_id;
		$this->results = $results;
		$this->count = (int) $count;
	}

	public function toArray()
	{
		return array(
			'query' => $this->query,
			'category_id' => $this->category_id,
			'count' => $this->count,
			'results' => $this->results,
		);
	}
}
