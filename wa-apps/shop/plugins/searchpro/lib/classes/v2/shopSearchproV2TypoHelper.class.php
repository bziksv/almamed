<?php

/**
 * Коррекция опечаток для suggest/page v2, когда finder находит substring-совпадения
 * (напр. «атоскоп» → «негатоскоп» вместо «отоскоп») или не находит ничего
 * (напр. «отоскап» → «отоскоп», «отоскап лфцу» → «отоскоп лфцу»).
 */
class shopSearchproV2TypoHelper
{
	/** @var array<string, string[]> Частые путаницы гласных RU */
	private static $vowel_alts_ru = array(
		'а' => array('о', 'я'),
		'о' => array('а', 'у'),
		'е' => array('и', 'ё'),
		'ё' => array('е', 'о'),
		'и' => array('е', 'ы'),
		'ы' => array('и'),
		'у' => array('о', 'ю'),
		'ю' => array('у'),
		'э' => array('е'),
		'я' => array('а', 'е'),
	);

	/** @var array<string, string[]> */
	private static $vowel_alts_en = array(
		'a' => array('o', 'e'),
		'o' => array('a', 'u'),
		'e' => array('i', 'a'),
		'i' => array('e'),
		'u' => array('o'),
		'y' => array('i'),
	);

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
		$words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
		if (!$words) {
			return array();
		}

		foreach ($words as $i => $word) {
			if (self::looksLikeWrongLayout($word)) {
				continue;
			}
			foreach (self::wordCandidates($word) as $fixed_word) {
				$copy = $words;
				$copy[$i] = $fixed_word;
				$candidates[] = implode(' ', $copy);
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
			$candidate = trim((string) $candidate);
			if ($candidate === '' || $candidate === $query) {
				continue;
			}
			if (!in_array($candidate, $unique, true)) {
				$unique[] = $candidate;
			}
			if (count($unique) >= 36) {
				break;
			}
		}

		return $unique;
	}

	/**
	 * Слова вроде «лфцу» (KAWE в русской раскладке) — не трогаем опечатками,
	 * их чинит KeyboardLayoutHelper.
	 */
	private static function looksLikeWrongLayout($word)
	{
		$word = (string) $word;
		if (!preg_match('/^[а-яё]+$/ui', $word) || mb_strlen($word, 'UTF-8') < 3) {
			return false;
		}

		$vowel_count = preg_match_all('/[аеёиоуыэюя]/ui', $word);
		// Мало гласных относительно длины → почти наверняка раскладка, не опечатка.
		if ($vowel_count <= 1 && mb_strlen($word, 'UTF-8') >= 4) {
			return true;
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	private static function wordCandidates($word)
	{
		$word = (string) $word;
		$len = mb_strlen($word, 'UTF-8');
		if ($len < 4) {
			return array();
		}

		$out = array();

		if (preg_match('/^[а-яё]+$/ui', $word)) {
			// 1) mid-vowel (отоскап→отоскоп)
			foreach (self::vowelSwapCandidates($word, self::$vowel_alts_ru) as $c) {
				$out[] = $c;
			}
			// 2) перестановки букв (отсокпо→отоскоп)
			foreach (self::adjacentSwapCandidates($word) as $c) {
				$out[] = $c;
			}
			// 3) первая буква (атоскоп→отоскоп)
			$rest = mb_substr($word, 1, null, 'UTF-8');
			foreach (array('о', 'а', 'э', 'и', 'у', 'е') as $prefix) {
				$out[] = $prefix . $rest;
			}
		} elseif (preg_match('/^[a-z]+$/i', $word)) {
			foreach (self::vowelSwapCandidates($word, self::$vowel_alts_en) as $c) {
				$out[] = $c;
			}
			foreach (self::adjacentSwapCandidates($word) as $c) {
				$out[] = $c;
			}
			$rest = substr($word, 1);
			foreach (array('o', 'a', 'e', 'i', 'u') as $prefix) {
				$out[] = $prefix . $rest;
			}
		}

		return $out;
	}

	/**
	 * Соседние перестановки букв (1 и 2 раза) — частые «перепутал порядок».
	 * Двойные VC-перестановки первыми: «отсокпо» → «отоскоп».
	 *
	 * @return string[]
	 */
	private static function adjacentSwapCandidates($word)
	{
		$chars = array();
		$len = mb_strlen($word, 'UTF-8');
		for ($i = 0; $i < $len; $i++) {
			$chars[] = mb_substr($word, $i, 1, 'UTF-8');
		}
		if (count($chars) < 2) {
			return array();
		}

		$out = array();
		$n = count($chars);
		$vowels = 'аеёиоуыэюяaeiouy';

		$swap = function (array $c, $a, $b) {
			$tmp = $c[$a];
			$c[$a] = $c[$b];
			$c[$b] = $tmp;
			return $c;
		};
		$is_vowel = function ($ch) use ($vowels) {
			return mb_strpos($vowels, mb_strtolower($ch, 'UTF-8'), 0, 'UTF-8') !== false;
		};
		$is_mixed_pair = function ($c, $i) use ($is_vowel) {
			return $is_vowel($c[$i]) !== $is_vowel($c[$i + 1]);
		};

		// 1) Две VC-перестановки (самый частый «скрамбл»).
		if ($n >= 5 && $n <= 12) {
			for ($i = 0; $i < $n - 1; $i++) {
				if (!$is_mixed_pair($chars, $i)) {
					continue;
				}
				for ($j = $i + 1; $j < $n - 1; $j++) {
					if (!$is_mixed_pair($chars, $j)) {
						continue;
					}
					$c = $swap($chars, $i, $i + 1);
					$c = $swap($c, $j, $j + 1);
					$out[] = implode('', $c);
				}
			}
		}

		// 2) Одна соседняя перестановка.
		for ($i = 0; $i < $n - 1; $i++) {
			$out[] = implode('', $swap($chars, $i, $i + 1));
		}

		// 3) Остальные двойные перестановки.
		if ($n >= 5 && $n <= 12) {
			for ($i = 0; $i < $n - 1; $i++) {
				for ($j = $i + 1; $j < $n - 1; $j++) {
					if ($is_mixed_pair($chars, $i) && $is_mixed_pair($chars, $j)) {
						continue; // уже в п.1
					}
					$c = $swap($chars, $i, $i + 1);
					$c = $swap($c, $j, $j + 1);
					$out[] = implode('', $c);
				}
			}
		}

		return $out;
	}

	/**
	 * Одна замена гласной за раз (а↔о и т.п.) — «отоскап» → «отоскоп».
	 *
	 * @param array<string, string[]> $alts
	 * @return string[]
	 */
	private static function vowelSwapCandidates($word, array $alts)
	{
		$len = mb_strlen($word, 'UTF-8');
		$out = array();
		$lower = mb_strtolower($word, 'UTF-8');

		for ($i = 0; $i < $len; $i++) {
			$ch = mb_substr($lower, $i, 1, 'UTF-8');
			if (!isset($alts[$ch])) {
				continue;
			}
			$orig = mb_substr($word, $i, 1, 'UTF-8');
			$is_upper = ($orig !== $ch && mb_strtoupper($ch, 'UTF-8') === $orig);
			foreach ($alts[$ch] as $alt) {
				$repl = $is_upper ? mb_strtoupper($alt, 'UTF-8') : $alt;
				$out[] = mb_substr($word, 0, $i, 'UTF-8') . $repl . mb_substr($word, $i + 1, null, 'UTF-8');
			}
		}

		return $out;
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
