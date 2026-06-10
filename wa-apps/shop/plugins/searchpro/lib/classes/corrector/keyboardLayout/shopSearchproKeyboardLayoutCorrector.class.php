<?php

/**
 * Класс для исправления неправильной раскладки в запросе
 */
class shopSearchproKeyboardLayoutCorrector extends shopSearchproCorrector
{
	public static $keyboard_symbols = array(
		'q' => 'й',
		'w' => 'ц',
		'e' => 'у',
		'r' => 'к',
		't' => 'е',
		'y' => 'н',
		'u' => 'г',
		'i' => 'ш',
		'o' => 'щ',
		'p' => 'з',
		'[' => 'х',
		']' => 'ъ',
		'a' => 'ф',
		's' => 'ы',
		'd' => 'в',
		'f' => 'а',
		'g' => 'п',
		'h' => 'р',
		'j' => 'о',
		'k' => 'л',
		'l' => 'д',
		';' => 'ж',
		'\'' => 'э',
		'z' => 'я',
		'x' => 'ч',
		'c' => 'с',
		'v' => 'м',
		'b' => 'и',
		'n' => 'т',
		'm' => 'ь',
		',' => 'б',
		'.' => 'ю'
	);

	public $mode = 'smart';

	public function setMode($mode)
	{
		$this->mode = $mode;
	}

	public function getMode()
	{
		return $this->mode;
	}

	public function getSmartCorrector()
	{
		if(!isset($this->smart_corrector)) {
			$this->smart_corrector = new shopSearchproKeyboardLayoutSmartCorrector();
		}

		return $this->smart_corrector;
	}

	/**
	 * Исправяет раскладку в строке в зависимости от установленного режима поиска ошибок
	 * @param string $query
	 * @param array $options
	 * @throws shopSearchproException
	 * @return string
	 */
	public function fixQuery($query, $options = array())
	{
		if(empty($query)) {
			throw new shopSearchproException('EMPTY_QUERY');
		}

		switch($this->getMode()) {
			case 'smart':
				$fixed_query = $this->getSmartCorrector()->fixQuery($query);

				if($fixed_query)
					return $fixed_query;
				break;
			case 'normal':
				if($this->getLanguage($query) === 'en') {
					return $this->convert($query, 'en-ru');
				} else {
					return $this->convert($query, 'ru-en');
				}
				break;
			case 'normal-ru-en':
				return $this->convert($query, 'ru-en');
				break;
			case 'normal-en-ru':
				return $this->convert($query, 'en-ru');
				break;
			default:
				throw new shopSearchproException('UNKNOWN_KEYBOARD_LAYOUT_MODE');
				break;
		}
	}

	public static function getLanguage($query)
	{
		preg_match_all("/[а-яё]/", $query, $ru_matches);
		preg_match_all("/[a-z]/", $query, $en_matches);

		return ($en_matches[0] > $ru_matches[0]) ? 'en' : 'ru';
	}

	public static function convert($query, $state)
	{
		switch($state) {
			case 'ru-en':
				return strtr($query, array_flip(self::$keyboard_symbols));
				break;
			case 'en-ru':
				return strtr($query, self::$keyboard_symbols);
				break;
		}
	}
}