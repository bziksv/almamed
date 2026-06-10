<?php

function smarty_modifier_sep($array, $sep = ' ')
{
	if (!is_array($array))
	{
		$array = array($array);
	}

	return implode($sep, $array);
}

