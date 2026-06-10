<?php


class shopSeofilterSepCommaModifier extends shopSeofilterArrayModifier
{
	public function modify($source)
	{
		return $source;
	}

	public function getSep()
	{
		return ', ';
	}
}