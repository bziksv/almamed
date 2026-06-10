<?php

class shopBreadcrumbsBreadcrumbsChain
{
	/** @var shopBreadcrumbsBreadcrumbsElement[] */
	private $elements = array();
	private $path = null;

	public function add(shopBreadcrumbsBreadcrumbsElement $element)
	{
		$this->elements[] = $element;

		$this->path = null;
	}

	public function path()
	{
		if (!count($this->elements))
		{
			return array();
		}

		if ($this->path === null)
		{
			$this->path = array();

			/** @var shopBreadcrumbsBreadcrumbsElement $element */
			foreach ($this->elements as $element)
			{
				foreach ($element->items() as $item)
				{
					$this->path[] = $item;
				}
			}
		}

		return $this->path;
	}

	public function lastItem()
	{
		$path = $this->path();

		return count($path)
			? $path[count($path) - 1]
			: null;
	}
}