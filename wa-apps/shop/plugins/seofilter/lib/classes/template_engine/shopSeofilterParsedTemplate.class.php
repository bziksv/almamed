<?php

/**
 * @property $h1
 * @property $description
 * @property $meta_title
 * @property $meta_keywords
 * @property $meta_description
 * @property $og_meta_title
 * @property $og_meta_description
 */
class shopSeofilterParsedTemplate
{
	private $_template = array(
		'h1' => '',
		'description' => '',
		'meta_title' => '',
		'meta_keywords' => '',
		'meta_description' => '',
		'og_meta_title' => '',
		'og_meta_description' => '',
	);

	public function __construct($template)
	{
		foreach ($this->_template as $tag => $v)
		{
			if (isset($template[$tag]) && strlen($template[$tag]) > 0)
			{
				$this->_template[$tag] = $template[$tag];
			}
		}
	}

	function __get($name)
	{
		if (($name === 'og_meta_title' || $name === 'og_meta_description') && !strlen($this->_template[$name]))
		{
			return $this->_template[str_replace('og_', '', $name)];
		}
		if (isset($this->_template[$name]))
		{
			return $this->_template[$name];
		}

		throw new waException("Unknown field [{$name}]");
	}

	function __set($name, $value)
	{
		if (isset($this->_template[$name]))
		{
			$this->_template[$name] = $value;
		}
		else
		{
			throw new waException("Unknown field [{$name}]");
		}
	}

	function __isset($name)
	{
		if (isset($this->_template[$name]))
		{
			return empty($this->_template[$name]);
		}

		return false;
	}

	public function getAsArray()
	{
		return $this->_template;
	}
}