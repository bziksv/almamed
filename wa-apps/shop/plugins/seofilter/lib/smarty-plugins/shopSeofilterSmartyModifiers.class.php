<?php

class shopSeofilterSmartyModifiers
{
	/**
	 * @param Smarty $smarty
	 */
	public function registerModifiers($smarty)
	{
		foreach ($this->getModifiers() as $modifier)
		{
			$smarty->registerPlugin('modifier', $modifier, array($this, $modifier));
		}
	}

	private function getModifiers()
	{
		return array('lcfirst', 'ucfirst', 'sep');
	}

	public function lcfirst($string)
	{
		$fc = mb_strtolower(mb_substr($string, 0, 1));
		return $fc . mb_substr($string, 1);
	}

	public function ucfirst($string)
	{
		$fc = mb_strtoupper(mb_substr($string, 0, 1));
		return $fc . mb_substr($string, 1);
	}

	public function sep($array, $sep = ' ')
	{
		if (!is_array($array))
		{
			$array = array($array);
		}

		return implode($sep, $array);
	}
}