<?php

function smarty_modifier_lcfirst($string)
{
	$fc = mb_strtolower(mb_substr($string, 0, 1));

	return $fc . mb_substr($string, 1);
}

