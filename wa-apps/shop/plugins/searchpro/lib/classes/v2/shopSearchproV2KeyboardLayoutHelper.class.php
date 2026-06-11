<?php

/**
 * Дополнительные варианты запроса при ошибке раскладки (v2).
 * Finder уже вызывает KeyboardLayout-корректор; здесь — fallback для «почти правильных» латинских строк.
 */
class shopSearchproV2KeyboardLayoutHelper
{
	/**
	 * @return string[]
	 */
	public static function candidates($query)
	{
		$query = trim((string) $query);
		if ($query === '' || !preg_match('/^[a-z[\];\',\.\/\-]+$/iu', $query)) {
			return array();
		}

		$candidates = array();
		$latin_variants = array($query);

		// f↔t — частая ошибка (f→«а», t→«е» на ЙЦУКЕН при наборе RU в EN-раскладке)
		if (strpos($query, 'f') !== false) {
			$latin_variants[] = str_replace('f', 't', $query);
		}
		if (strpos($query, 't') !== false) {
			$latin_variants[] = str_replace('t', 'f', $query);
		}

		$latin_variants = array_unique($latin_variants);

		foreach ($latin_variants as $variant) {
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

		try {
			$smart = new shopSearchproKeyboardLayoutSmartCorrector();
			$smart_fixed = $smart->fixQuery($query);
			if ($smart_fixed && $smart_fixed !== $query) {
				$candidates[] = $smart_fixed;
			}
		} catch (Exception $e) {
		}

		return array_values(array_unique($candidates));
	}
}
