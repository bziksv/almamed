<?php

class shopSearchproPluginFrontendContentParserResult
{
	private $results;
	protected $entities = array();

	public function __construct($results)
	{
		$this->results = $results;
	}

	public function getEntity($entity)
	{
		if(array_key_exists($entity, $this->results)) {
			return $this->results[$entity];
		}

		return null;
	}

	public function getH1()
	{
		return $this->getEntity('h1');
	}

	public function getContent()
	{
		return $this->getEntity('content');
	}
}