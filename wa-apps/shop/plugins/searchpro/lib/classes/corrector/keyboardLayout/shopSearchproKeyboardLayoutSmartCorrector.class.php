<?php

class shopSearchproKeyboardLayoutSmartCorrector extends shopSearchproCorrector
{
	private $cyrillic_corrector;
	private $corrector_storage;

	private $en_correct;
	private $ru_correct;

	private $table_flip;
	private $words = array();

	private $is_flip = false;
	private $find_different_variant = false;
	private $method = 0;

	public function __construct($params = array())
	{
		parent::__construct($params);

		$this->cyrillic_corrector = new shopSearchproKeyboardLayoutSmartCyrillicCorrector();
		$this->corrector_storage = new shopSearchproCorrectorStorage('KeyboardLayoutSmart');

		$this->preExecute();
	}

	public function setFindDifferentVariant()
	{
		$this->find_different_variant = true;
	}

	private function getCyrillicCorrector()
	{
		return $this->cyrillic_corrector;
	}

	private function getCorrectorStorage()
	{
		return $this->corrector_storage;
	}

	private function getData($name = null, $default = null)
	{
		return $this->getCorrectorStorage()->getData($name, $default);
	}

	private function preExecute()
	{
		$this->en_correct = '/(?: (?:' . $this->getData('ru_filtered') . ')
                                   (?: (?:' . $this->getData('en_unique_chars') . ') | (?:' . $this->getData('en_special_chars') . '){2} )
                                 | (?:' . $this->getData('en_special_chars') . ')
                                   (?:' . $this->getData('ru_filtered') . ')
                                   (?:' . $this->getData('en_special_chars') . ')
                                 | (?: (?:' . $this->getData('en_unique_chars') . ') | (?:' . $this->getData('en_special_chars') . '){2} )
                                   (?:' . $this->getData('ru_filtered') . ')
                               )
                              /sxSX';

		$this->ru_correct = '/(?: (?:' . $this->getData('en_special_chars') . ')
                                   (?: (?:' . $this->getData('ru_unique_chars') . ') | (?:' . $this->getData('ru_filtered') . '){2} )
                                 | (?:' . $this->getData('ru_filtered') . ')
                                   (?:' . $this->getData('en_special_chars') . ')
                                   (?:' . $this->getData('ru_filtered') . ')
                                 | (?: (?:' . $this->getData('ru_unique_chars') . ') | (?:' . $this->getData('ru_filtered') . '){2} )
                                   (?:' . $this->getData('en_special_chars') . ')
                               )
                              /sxSX';

		$this->table_flip = array(
			0 => array_flip($this->getData('table->0')),
			1 => array_flip($this->getData('table->1')),
		);
	}

	/**
	 * Умный поиск ошибок в раскладке
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

		$restore = array();
		$query = $this->getCyrillicCorrector()->deleteDiactrical($query, $restore);
		$this->modifyWords($query);
		$this->modifyRuLetters($query);
		$query = $this->getCyrillicCorrector()->returnDiactrical($query, $restore);

		return $query;
	}

	/**
	 * Корректирует слова в тексте
	 * @param string $query
	 */
	private function modifyWords(&$query)
	{
		$query = preg_replace_callback('/(?> (' . $this->getData('en') . ')
                                         | (' . $this->getData('ru') . ')
                                         | (' . $this->getData('special_chars') . ')
                                       ){3,}+
                                      /sxSX', array($this, 'modifyWordsCallback'), $query);
	}

	private function modifyWordsCallback(&$matches)
	{
		$word = $matches[0];

		$suggestions = array();

		if(!empty($matches[1]) && !empty($matches[2])) {
			$this->method = 0;
			$this->is_flip = true;
			$suggest = $this->suggestWordWithinRegexp($word, $this->ru_correct);
			if($word !== $suggest && !$this->isMixed($suggest))
				$suggestions['ru0'] = $suggest;

			$this->method = 1;
			$this->is_flip = true;
			$suggest = $this->suggestWordWithinRegexp($word, $this->ru_correct);
			if($word !== $suggest)
				$suggestions['ru1'] = $suggest;

			$this->method = 0;
			$this->is_flip = false;
			$suggest = $this->suggestWordWithinRegexp($word, $this->en_correct);
			if($word !== $suggest && !$this->isMixed($suggest))
				$suggestions['en0'] = $suggest;

			$this->method = 1;
			$this->is_flip = false;
			$suggest = $this->suggestWordWithinRegexp($word, $this->en_correct);
			if($word !== $suggest)
				$suggestions['en1'] = $suggest;
		} elseif(!empty($matches[1]) && strlen($word) >= 4) {
			$suggestions['en1'] = $word;
			$suggestions['ru1'] = strtr($word, $this->table_flip[1]);
		} elseif(!empty($matches[2]) && strlen($word) >= 8) {
			$suggestions['ru1'] = $word;
			$suggestions['en1'] = strtr($word, $this->getData('table->1'));
		} else
			return $word;

		$suggestions = array_unique($suggestions);

		$count = count($suggestions);
		if($count === 0) {
			$suggest = $word;
		} else {
			$suggest = $this->takeVariant($word, $suggestions, !empty($matches[3]));
		}

		if($suggest !== $word) {
			$this->words[$word] = $suggest;
		}

		return $suggest;
	}

	private function isMixed($word)
	{
		return preg_match('/(?:' . $this->getData('en') . ')/sxSX', $word) &&
			preg_match('/(?:' . $this->getData('ru_filtered') . ')/sxSX', $word);
	}

	private function suggestWordWithinRegexp($word, $regexp)
	{
		do {
			$word = preg_replace_callback($regexp, array(&$this, 'suggestWordWithinRegexpCallback'), $w = $word);
		} while($w !== $word);

		return $word;
	}

	private function suggestWordWithinRegexpCallback($matches)
	{
		$word = &$matches[0];
		return strtr($word, $this->is_flip ? $this->table_flip[$this->method] : $this->getData("table->{$this->method}"));
	}

	private function takeVariant($word, $suggestions, $is_sc)
	{
		$memory_suggestions = $suggestions;

		foreach($suggestions as $type => $w) {
			$lang = substr($type, 0, 2);

			if(!$this->isGramsExists($w, $lang))
				unset($suggestions[$type]);
		}

		if(count($suggestions) === 0) {
			if($this->find_different_variant) {
				foreach($memory_suggestions as $w) {
					if($w != $word) {
						return $w;
					}
				}
			} else
				return $word;
		}

		$suggest = end($suggestions);

		if($is_sc && !preg_match('/' . $this->getData('special_chars') . '/sSX', $suggest))
			return $suggest;

		$sc_count = 0;
		$suggest = preg_replace('/' . $this->getData('special_chars') . '/sSX', '', $suggest, -1, $sc_count);
		if($sc_count > 0 && $sc_count > mb_strlen($suggest))
			return $word;

		return reset($suggestions);
	}

	/**
	 * Проверка на существование N-граммов, встречаемых в слове, в конкретном языке
	 * @param string $word
	 * @param string $lang
	 * @return bool
	 */
	private function isGramsExists($word, $lang)
	{
		switch($lang) {
			case 'en':
				$word = strtolower($word);
				break;
			case 'ru':
				$word = mb_strtolower($word);
				break;
		}

		/**
		 * Проверка 4 согласных подряд
		 */
		if(preg_match('/(?:' . $this->getData("consonants->$lang") . '){4}/sxSX', $word, $matches_1)
			&& !array_key_exists($matches_1[0], $this->getData("unexisting_fourgrams->$lang")))
			return false;

		/**
		 * Проверка 3 гласных подряд
		 */
		if(preg_match('/(?:' . $this->getData("vowels->$lang") . '){3}/sxSX', $word, $matches_2)
			&& !array_key_exists($matches_2[0], $this->getData("unexisting_trigrams->$lang")))
			return false;

		/**
		 * Общая проверка по несуществующим биграммам
		 */
		$length = mb_strlen($word);
		for($pos = 0, $limit = $length - 1; $pos < $limit; $pos++) {
			$ss = mb_substr($word, $pos, 2);

			if($pos === 0)
				$ss = ' ' . $ss;
			elseif($pos === $limit - 1)
				$ss = $ss . ' ';

			if(array_key_exists($ss, $this->getData('unexisting_bigrams')))
				return false;
		}

		return true;
	}

	/**
	 * Корректирует строку, заменяя русские буквы, похожие на английские, с рядом стоящими цифрами на латинские
	 * @param string $query
	 */
	private function modifyRuLetters(&$query)
	{
		$query = preg_replace_callback('~(?: (?<=[^-_/]|^)
                                           (?:' . $this->getData('ru_similar_chars') . ')++
                                           (?= (?:' . $this->getData('en') . '|[-_/])*+ (?<=[^-_/]|' . $this->getData('en') . '[-_/])
                                               \d [-\d_/]*+ (?!' . $this->getData('ru_unique_chars') . ')
                                           )
                                         | (?<=[^-_/]|^)
                                           \d  (?:' . $this->getData('en') . '|[-_/])*+ (?<=[^-_/]|' . $this->getData('en') . '[-_/])
                                           \K
                                           (?:' . $this->getData('ru_similar_chars') . ')++
                                           (?= [-\d_/]*+ (?!' . $this->getData('ru_unique_chars') . ') )
                                       )
                                      ~sxSX', array($this, 'modifyRuLettersCallback'), $query);
	}

	private function modifyRuLettersCallback(&$a)
	{
		$entry =& $a[0];
		$s = strtr($entry, $this->getData('table->0'));
		if($s !== $entry)
			$this->words[$entry] = $s;
		return $s;
	}
}