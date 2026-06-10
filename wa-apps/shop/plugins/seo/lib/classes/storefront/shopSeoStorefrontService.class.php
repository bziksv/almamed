<?php


class shopSeoStorefrontService
{
	private $source;
	
	public function __construct(shopSeoStorefrontSource $source)
	{
		$this->source = $source;
	}
	
	public function getStorefronts()
	{
		return $this->source->getStorefronts();
	}
	
	public function getCurrentStorefront()
	{
		return $this->source->getCurrentStorefront();
	}
}