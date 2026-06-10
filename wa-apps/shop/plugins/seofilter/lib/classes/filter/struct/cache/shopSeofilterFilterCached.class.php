<?php

class shopSeofilterFilterCached
{
	private $id;
	private $url;

	public function __construct(shopSeofilterFilter $filter)
	{
		$this->id = $filter->id;
		$this->url = $filter->url;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getUrl()
	{
		return $this->url;
	}
}