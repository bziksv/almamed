<?php

abstract class shopSeofilterReplacesSet implements shopSeofilterIReplacer
{
	protected $view;

	public function __construct(shopSeofilterViewBuffer $view)
	{
		$this->view = $view;
	}

	public function fetch($template)
	{
		$result = $template;

		foreach ($this->getReplaces() as $replacer)
		{
			if ($replacer instanceof shopSeofilterIReplacer)
			{
				$result = $replacer->fetch($result);
			}
		}

		return $result;
	}

	public function toSmarty($template)
	{
		$result = $template;

		foreach ($this->getReplaces() as $replacer)
		{
			if ($replacer instanceof shopSeofilterIReplacer)
			{
				$result = $replacer->toSmarty($result);
			}
		}

		return $result;
	}

	abstract public function getReplaces();
}