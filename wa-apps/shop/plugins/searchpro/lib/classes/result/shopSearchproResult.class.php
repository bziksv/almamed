<?php

class shopSearchproResult
{
	protected $count;
	protected $results;
	protected $collection;
	protected $initial_collection;

	public function __construct($results, $collection = null)
	{
		$this->results = $results;

		if($collection !== null && $collection instanceof shopSearchproProductsCollection) {
			$this->collection = $collection;
		}
	}

	public function isCorrect()
	{
		return is_array($this->results);
	}

	public function getInitial()
	{
		return $this->results;
	}

	public function getIds()
	{
		return array_keys($this->results);
	}

	public function getCount()
	{
		if(!isset($this->count)) {
			$this->count = count($this->results);
		}

		return $this->count;
	}

	public function isEmpty()
	{
		$count = $this->getCount();

		return $count === 0;
	}

	/**
	 * @return shopSearchproProductsCollection
	 */
	public function getCollection()
	{
		if(!isset($this->collection)) {
			$this->collection = new shopSearchproProductsCollection($this->isCorrect() ? array_keys($this->results) : array());
		}

		return $this->collection;
	}

	/**
	 * @return shopProductsCollection
	 */
	public function getInitialCollection()
	{
		if(!isset($this->initial_collection)) {
			$this->initial_collection = new shopProductsCollection($this->isCorrect() ? array_keys($this->results) : array());
		}

		return $this->initial_collection;
	}

	public function getPriceRange()
	{
		$collection = $this->getCollection();

		return $collection->getPriceRange();
	}
}