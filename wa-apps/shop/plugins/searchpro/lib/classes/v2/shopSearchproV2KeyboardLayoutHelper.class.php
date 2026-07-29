<?php

/**
 * Дополнительные варианты запроса при ошибке раскладки (v2).
 * Пример: «отоскоп лфцу» (KAWE в русской раскладке) → «отоскоп kawe».
 */
class shopSearchproV2KeyboardLayoutHelper
{
	/**
	 * @return string[]
	 */
	public static function candidates($query)
	{
		$query = trim((string) $query);
		if ($query === '') {
			return array();
		}

		$candidates = array();

		// Smart: flips only words that look like the wrong layout (keeps «отоскоп»).
		try {
			$corrector = new shopSearchproKeyboardLayoutCorrector();
			$corrector->setMode('smart');
			$smart = $corrector->fixQuery($query);
			if ($smart && $smart !== $query) {
				$candidates[] = $smart;
			}
		} catch (Exception $e) {
		}

		// Per-word ru→en / en→ru (one word at a time) — covers brands typed in wrong layout.
		foreach (self::perWordFlipCandidates($query) as $variant) {
			$candidates[] = $variant;
		}

		// Pure latin query: f↔t typos + normal/smart extras.
		if (preg_match('/^[a-z[\];\',\.\/\-]+$/iu', $query)) {
			$latin_variants = array($query);
			if (strpos($query, 'f') !== false) {
				$latin_variants[] = str_replace('f', 't', $query);
			}
			if (strpos($query, 't') !== false) {
				$latin_variants[] = str_replace('t', 'f', $query);
			}
			foreach (array_unique($latin_variants) as $variant) {
				if ($variant === $query) {
					continue;
				}
				$converted = shopSearchproKeyboardLayoutCorrector::convert($variant, 'en-ru');
				if ($converted && $converted !== $query) {
					$candidates[] = $converted;
				}
			}

			try {
				$corrector = new shopSearchproKeyboardLayoutCorrector();
				$corrector->setMode('normal');
				$normal = $corrector->fixQuery($query);
				if ($normal && $normal !== $query) {
					$candidates[] = $normal;
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
		}

		return $unique;
	}

	/**
	 * @return string[]
	 */
	private static function perWordFlipCandidates($query)
	{
		$words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
		if (!$words) {
			return array();
		}

		$out = array();
		$count = count($words);

		for ($i = 0; $i < $count; $i++) {
			$word = $words[$i];
			$flipped = null;

			if (preg_match('/^[а-яё]+$/ui', $word) && mb_strlen($word) >= 3) {
				$flipped = shopSearchproKeyboardLayoutCorrector::convert($word, 'ru-en');
			} elseif (preg_match('/^[a-z]+$/i', $word) && strlen($word) >= 3) {
				$flipped = shopSearchproKeyboardLayoutCorrector::convert($word, 'en-ru');
			}

			if (!$flipped || $flipped === $word) {
				continue;
			}

			$copy = $words;
			$copy[$i] = $flipped;
			$out[] = implode(' ', $copy);
		}

		return $out;
	}
}
