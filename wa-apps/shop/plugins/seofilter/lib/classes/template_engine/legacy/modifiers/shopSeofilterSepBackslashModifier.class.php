<?php


class shopSeofilterSepBackslashModifier extends shopSeofilterArrayModifier
{
	public function modify($source)
	{
		return $source;
	}

	public function getSep()
	{
		return ' \ ';
	}
}