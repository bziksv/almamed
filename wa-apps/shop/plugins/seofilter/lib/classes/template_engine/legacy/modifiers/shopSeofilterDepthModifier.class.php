<?php

class shopSeofilterDepthModifier extends shopSeofilterArrayModifier
{
	public function modify($source, $arg = null)
	{
		$result = array();
		$depths = explode(',', $arg);

		if (is_array($source))
		{
			foreach ($source as $i => $value)
			{
				if (in_array($i+1, $depths))
				{
					$result[$i] = $value;
				}
			}
		}

		return $result;
	}
}