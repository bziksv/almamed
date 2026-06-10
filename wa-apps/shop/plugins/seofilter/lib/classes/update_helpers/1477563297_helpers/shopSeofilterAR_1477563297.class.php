<?php

class shopSeofilterAR_1477563297
{
	public $attributes;

	public function __construct($attributes = null)
	{
		$this->attributes = is_array($attributes)
			? $attributes
			: array();
	}

	function __set($name, $value)
	{
		$this->attributes[$name] = $value;
	}
}