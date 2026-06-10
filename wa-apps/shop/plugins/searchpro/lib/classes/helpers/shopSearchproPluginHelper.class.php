<?php

class shopSearchproPluginHelper
{
	public static function prepareQuery($query)
	{
		$query = str_replace('%SLASH%', '/', $query);
		$query = mb_strtolower($query);
		$query = strip_tags($query);
		$query = preg_replace('/\s{2,}/', ' ', $query);
		$query = trim($query);

		return $query;
	}

	public static function sliceQuery($query, $excluded_symbols = '')
	{
		$query = mb_strtolower($query);
		$query = strip_tags($query);
		$query = preg_replace('/[^a-zа-яёЁёЁЇїІіЄєҐґ0-9\._\s\\\\\-\/]/iu', ' ', $query);
		$query = preg_replace('/\s{2,}/', ' ', $query);

		$words = explode(' ', $query);
		$words = array_filter($words);

		return $words;
	}

	public static function createGrams($word)
	{
		$length = mb_strlen($word);

		$grams = array();

		for($i = -2; $i < $length; $i++) {
			if($i < 0) {
				$str = mb_substr($word, 0, 3 - abs($i));
				$pad_type = STR_PAD_RIGHT;
			} else {
				$str = mb_substr($word, $i, 3);
				$pad_type = STR_PAD_BOTH;
			}

			if($length - $i < 3) {
				$pad_type = STR_PAD_LEFT;
			}

			$str = self::strPad($str, 3, '_', $pad_type);

			$grams[] = $str;
		}

		return implode(' ', $grams);
	}

	public static function strPad($str, $pad_length, $pad_string, $pad_type = STR_PAD_RIGHT)
	{
		$diff = strlen($str) - mb_strlen($str); // Хак для использования мультибайтового str_pad

		$str = str_pad($str, $pad_length + $diff, $pad_string, $pad_type);

		return $str;
	}
}
