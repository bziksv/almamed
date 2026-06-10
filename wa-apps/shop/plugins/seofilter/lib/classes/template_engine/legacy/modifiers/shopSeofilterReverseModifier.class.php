<?php


class shopSeofilterReverseModifier extends shopSeofilterArrayModifier
{
	public function modify($source)
	{
		if (is_array($source))
		{
			return array_reverse($source);
		}

		return $source;
	}
}