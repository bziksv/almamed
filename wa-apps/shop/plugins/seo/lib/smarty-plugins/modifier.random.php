<?php

function smarty_modifier_random($words)
{
	if (!is_array($words) || count($words) === 0)
	{
		return '';
	}

	$key = waRequest::server('HTTP_HOST') . '/'
		. wa()->getRouting()->getRootUrl()
		. wa()->getRouting()->getCurrentUrl()
		. implode(',', $words);

	$hex_digits_count = ceil(count($words) / 16 - 1e-6);

	$hash = md5($key);
	$random = hexdec(substr($hash, -$hex_digits_count, $hex_digits_count));

	return $words[$random % count($words)];
}