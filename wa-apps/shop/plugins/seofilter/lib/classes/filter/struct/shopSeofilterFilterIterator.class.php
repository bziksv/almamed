<?php

class shopSeofilterFilterIterator implements Iterator
{
	const BUFFER_SIZE = 100;

	/** @var shopSeofilterFilterCollection */
	private $collection;

	private $offset;
	private $limit;

	private $buffer;

	private $total_count;
	private $loaded_count;

	private $current_offset;

	private $total_index;
	private $buffer_index;

	/**
	 * shopSeofilterFilterIterator constructor.
	 * @param shopSeofilterFilterCollection $collection
	 */
	public function __construct($collection)
	{
		$this->collection = $collection;

		$this->total_count = $collection->count();
		$this->offset = $collection->getOffset();
		$this->limit = $collection->getLimit();
	}

	/**
	 * @return shopSeofilterFilter
	 */
	public function current()
	{
		$attributes = $this->buffer[$this->buffer_index];

		$filter = new shopSeofilterFilter();
		$filter->setAttributes($attributes);
		$filter->setIsNewRecord(false);

		return $filter;
	}

	public function next()
	{
		$this->buffer_index++;
		$this->total_index++;
	}

	public function key()
	{
		return $this->total_index;
	}

	public function valid()
	{
		if ($this->buffer === null
			|| ($this->limit !== 0 && $this->total_index >= $this->limit)
			|| $this->total_index >= $this->total_count
		)
		{
			return false;
		}

		if ($this->buffer_index < self::BUFFER_SIZE && array_key_exists($this->buffer_index, $this->buffer))
		{
			return true;
		}
		else
		{
			$this->load();
			return array_key_exists($this->buffer_index, $this->buffer);
		}
	}

	public function rewind()
	{
		$this->total_index = 0;
		$this->loaded_count = 0;
		$this->current_offset = $this->offset;

		$this->load();
	}

	private function load()
	{
		try
		{
			$this->buffer = $this->collection->loadChunk($this->current_offset, self::BUFFER_SIZE);

			$this->loaded_count += count($this->buffer);
			$this->current_offset += self::BUFFER_SIZE;

			$this->buffer_index = 0;
		}
		catch (Exception $e)
		{
			$this->buffer = null;
			return false;
		}

		return true;
	}

	public function count()
	{
		return $this->total_count;
	}
}
