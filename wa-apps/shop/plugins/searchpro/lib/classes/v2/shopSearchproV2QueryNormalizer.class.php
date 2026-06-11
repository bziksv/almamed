<?php

class shopSearchproV2QueryNormalizer
{
	public function normalize($query)
	{
		return shopSearchproPluginHelper::prepareQuery($query);
	}

	public function isLongEnough($query, $min_length)
	{
		return mb_strlen($query) >= max(1, (int) $min_length);
	}
}
