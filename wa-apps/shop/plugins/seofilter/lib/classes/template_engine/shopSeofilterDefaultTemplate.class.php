<?php

/**
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $h1
 * @property string $description
 * @property string $storefront_name
 */
class shopSeofilterDefaultTemplate
{
	protected $templates;

	public static function model()
	{
		return new shopSeofilterDefaultTemplateModel();
	}

	public function __construct($templates)
	{
		$this->templates = $templates;
	}

	function __isset($name)
	{
		return isset($this->templates[$name]);
	}

	public function __get($name)
	{
		if (isset($this->templates[$name]))
		{
			return $this->templates[$name];
		}

		return null;
	}

	public function template()
	{
		return $this->templates;
	}
}