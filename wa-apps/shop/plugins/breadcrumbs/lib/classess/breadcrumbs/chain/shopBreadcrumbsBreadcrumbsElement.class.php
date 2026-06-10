<?php

abstract class shopBreadcrumbsBreadcrumbsElement
{
	protected $items = null;

	public function items()
	{
		if ($this->items === null)
		{
			$this->items = $this->initializeItems();
		}

		return $this->items;
	}

	abstract protected function initializeItems();
}