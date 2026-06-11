<?php

/**
 * Коррекция опечаток для suggest/page v2, когда finder находит substring-совпадения
 * (напр. «атоскоп» → «негатоскоп» вместо «отоскоп»).
 */
class shopSearchproV2TypoHelper
{
	/**
	 * @param array $products Список товаров с ключом name
	 */
	public static function isWeakMatch($query, array $products)
	{
		$query = trim((string) $query);
		if ($query === '' || mb_strlen($query) < 3 || !$products) {
			return false;
		}

		$needle = mb_strtolower($query, 'UTF-8');
		$checked = 0;

		foreach ($products as $product) {
			if ($checked++ >= 5) {
				break;
			}

			$name = mb_strtolower(trim((string) ifset($product, 'name', '')), 'UTF-8');
			if ($name === '') {
				continue;
			}

			if (mb_strpos($name, $needle) === 0) {
				return false;
			}

			if (preg_match('/^[^a-zа-яё0-9]*' . preg_quote($needle, '/') . '(?:\b|[\s\-–—])/ui', $name)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string[]
	 */
	public static function candidates($query, shopSearchproV2Settings $settings = null)
	{
		$query = trim((string) $query);
		if ($query === '') {
			return array();
		}

		$candidates = array();

		if (preg_match('/^[а-яё]+$/ui', $query) && mb_strlen($query) >= 4) {
			$rest = mb_substr($query, 1, null, 'UTF-8');
			foreach (array('о', 'а', 'э', 'и', 'у', 'е') as $prefix) {
				$candidates[] = $prefix . $rest;
			}
		}

		if (preg_match('/^[a-z]+$/i', $query) && strlen($query) >= 4) {
			$rest = substr($query, 1);
			foreach (array('o', 'a', 'e', 'i', 'u') as $prefix) {
				$candidates[] = $prefix . $rest;
			}
		}

		if ($settings && $settings->get('grams_status')) {
			try {
				$corrector = new shopSearchproGramsCorrector(array(
					'grams_mode' => $settings->get('grams_mode'),
				));
				$fixed = $corrector->fixQuery($query, $settings->get('grams_mode'));
				if ($fixed && $fixed !== $query && self::isAcceptableGramsFix($query, $fixed)) {
					$candidates[] = $fixed;
				}
			} catch (Exception $e) {
			}
		}

		$unique = array();
		foreach ($candidates as $candidate) {
			if ($candidate !== $query && !in_array($candidate, $unique, true)) {
				$unique[] = $candidate;
			}
		}

		return $unique;
	}

	/**
	 * Отсекает grams-исправления вроде «атоскоп» → «негатоскоп» (вложенное совпадение).
	 */
	protected static function isAcceptableGramsFix($query, $fixed)
	{
		$pos = mb_stripos($fixed, $query, 0, 'UTF-8');
		if ($pos === false) {
			return true;
		}
		if ($pos === 0) {
			return true;
		}

		return mb_strlen($fixed, 'UTF-8') <= mb_strlen($query, 'UTF-8') + 1;
	}
}
