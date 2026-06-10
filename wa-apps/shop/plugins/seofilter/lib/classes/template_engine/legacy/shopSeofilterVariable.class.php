<?php

class shopSeofilterVariable implements shopSeofilterIReplacer
{
	public function getModifiers()
	{
		return array(
			'lower' => new shopSeofilterLowerModifier(),
			'if_page_not_first' => new shopSeofilterIfPageNotFirstModifier(),
			'if_page_first' => new shopSeofilterIfPageFirstModifier(),
		);
	}

	public function fetch($template)
	{
		return preg_replace_callback(
			'/\{' . preg_quote($this->name) . '((?:\|[A-z0-9\_\-]+)*)\}/',
			array($this, 'variableReplace'),
			$template
		);
	}

	public function toSmarty($template)
	{
		return preg_replace_callback(
			'/\{' . preg_quote($this->name) . '((?:\|[A-z0-9\_\-]+)*)\}/',
			array('shopSeofilterSmartyEscaper', 'escape'),
			$template
		);
	}

	public function variableReplace(array $matches)
	{
		$string_modifiers = ifset($matches[1]);
		preg_match_all('/\|([A-z0-9\_\-]+)*/', $string_modifiers, $matches_modifiers);
		$found_modifiers = ifset($matches_modifiers[1], array());
		$modifiers = $this->getModifiers();
		$value = $this->value;

		foreach ($found_modifiers as $modifier)
		{
			$modifier = ifset($modifiers[$modifier]);

			if ($modifier instanceof shopSeofilterModifier)
			{
				$value = $modifier->modify($value);
			}
		}

		return $value;
	}

	public function __construct($name, $value)
	{
		if (preg_match('/^[A-z0-9\_\-]+$/', $name) === 0)
		{
			throw new Exception('Недопустимое имя переменной');
		}

		$this->name = $name;
		$this->value = $value;
	}

	private $name;
	private $value;
}