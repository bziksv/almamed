<?php

abstract class shopSearchproDetectorAction
{
	protected $params;

	final public function __construct($params)
	{
		$this->params = $params;
	}

	final protected function getParams()
	{
		return $this->params;
	}

	final protected function getParam($name)
	{
		if(!array_key_exists($name, $this->params)) {
			return null;
		}

		return $this->params[$name];
	}

	abstract public function execute();
}