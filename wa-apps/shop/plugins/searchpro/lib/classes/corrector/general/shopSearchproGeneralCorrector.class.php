<?php

class shopSearchproGeneralCorrector extends shopSearchproCorrector
{
	public function fixQuery($query, $options = array())
	{
		$query = preg_replace('/[^a-zа-яёЁёЁЇїІіЄєҐґ0-9\._\s\\\\\-\/]/iu', ' ', $query);

		return $query;
	}
}
