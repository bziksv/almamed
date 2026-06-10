<?php

class shopSeofilterLazyReplacer
{
	private $replacer;

	public function __construct(shopSeofilterIReplacer $replacer = null)
	{
		$this->replacer = $replacer;
	}

	public function preCompile($source, $template)
	{
		if (isset($this->replacer))
		{
			return $this->replacer->toSmarty($source);
		}

		return $source;
	}

	public function parseTemplate($string, $template)
	{
		if (isset($this->replacer))
			return $this->replacer->fetch($string);

		return $string;
	}
}