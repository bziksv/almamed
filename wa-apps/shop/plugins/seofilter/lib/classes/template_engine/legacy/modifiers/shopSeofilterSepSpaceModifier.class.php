<?php


class shopSeofilterSepSpaceModifier extends shopSeofilterArrayModifier
{
	public function modify($source)
	{
		return $source;
	}

	public function getSep()
	{
		return ' ';
	}
}