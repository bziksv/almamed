<?php

class shopSearchproIndex extends shopIndexSearch
{
	/**
	 * @return string
	 */
	private function getMode()
	{
		return ifset($this->options, 'mode', 'shop');
	}

	private function methodName($method, $is_throw = false, $mode = null)
	{
		if($mode === null) {
			$mode = $this->getMode();
		}

		$method_name = $mode . '_' . $method;

		if(!method_exists($this, $method_name)) {
			if($is_throw) {
				throw new waException("Метод {$method_name} не найден");
			} else {
				return $this->methodName($method, true, 'shop');
			}
		}

		return $method_name;
	}

	protected function getWordForms($word, $search = false)
	{
		$method_name = $this->methodName('getWordForms');

		return $this->$method_name($word, $search);
	}

	protected function plugin_getWordForms($word, $search = false)
	{
		$result = array();
		if ($this->options['form_ignore_numstart'] && preg_match("/[0-9]/", mb_substr($word, 0, 1))) {
			return $result;
		}

		$break_symbols = $this->options['form_break_symbols'];

		if ($break_symbols && strpbrk($word, $break_symbols) !== false) {
			$preg_quoted_break_symbols = preg_quote($break_symbols, '/');
			$result = preg_split("/[{$preg_quoted_break_symbols}]/u", $word, null, PREG_SPLIT_NO_EMPTY);
			if ($result) {
				$n = count($result);
				$w = "";
				for ($i = 0; $i < $n; $i++) {
					$result[$i] = $this->options['ignore'] ? str_replace($this->options['ignore'], '', $result[$i]) : $result[$i];
					$w .= $result[$i];
					if ($w) {
						$result[] = $w;
					}
				}
			}
		}

		if ($this->options['form_numbers'] && preg_match_all('/[0-9]+/is', $word, $matches)) {
			foreach ($matches[0] as $w) {
				$result[] = $w;
			}
		}

		if ($this->options['form_strnum'] && preg_match_all('/[^0-9]+[0-9]+/is', $word, $matches)) {
			foreach ($matches[0] as $w) {
				$result[] = $w;
			}
		}

		return $result;
	}

	protected function shop_getWordForms($word, $search = false)
	{
		return parent::getWordForms($word, $search);
	}

	public function getWordIds($string, $only_exist = false)
	{
		$method_name = $this->methodName('getWordIds');

		return $this->$method_name($string, $only_exist);
	}

	public function plugin_getWordIds($string, $only_exist = false)
	{
		$words = preg_split("/([\s,;:]+|[\.!\?](\s+|$))/su", $string, null, PREG_SPLIT_NO_EMPTY);

		$additional_words = array();

		foreach ($words as $i => $w) {
			if ($this->options['ignore']) {
				$clear_w = str_replace($this->options['ignore'], '', $w);
			} else {
				$clear_w = $w;
			}

			if ($clear_w) {
				$words[$i] = mb_strtolower($clear_w);

				if ($this->options['word_forms'] && $word_forms = $this->getWordForms($words[$i], $only_exist)) {
					$additional_words = array_merge($additional_words, $word_forms);
				}
			} else {
				unset($words[$i]);
			}
		}

		if ($additional_words) {
			$words = array_merge($words, $additional_words);
		}

		$words = array_unique($words);

		$word_masks = array();
		foreach($words as $key => $word) {
			if(mb_strlen($word) < $this->options['form_min_length']) {
				unset($words[$key]);
				$word_masks[] = shopSearchproPluginHelper::strPad($word, $this->options['form_min_length'], '?');
			}
		}

		list($word_ids, $rest_words) = $this->selectWordsData($words, $this->options['by_part'], true);

		return array($word_ids, $rest_words, $word_masks);
	}

	public function shop_getWordIds($string, $only_exist = false)
	{
		return parent::getWordIds($string, $only_exist);
	}

	protected function selectWordsData($words, $by_part = 0, $is_return_rest = false)
	{
		$search_words = array();
		$word_model = $this->getWordModel();

		$where = array();
		foreach($words as $w) {
			$w = trim($w);
			if($w) {
				$w = shopSearch::stem($w);
				$search_words[] = $w;

				$where[] = "name LIKE '" . $word_model->escape($w, 'like') . ($by_part && mb_strlen($w) >= $by_part ? '%' : '') . "'";
			}
		}

		if($where) {
			$sql = "SELECT `id`, `name` FROM {$word_model->getTableName()} WHERE " . implode(' OR ', $where);

			$words_data = $word_model->query($sql)->fetchAll($is_return_rest ? 'name' : null, true);

			$is_diff_counts = count($search_words) > count($words_data);

			$rest_words = array();
			if($is_return_rest && $is_diff_counts) {
				$rest_words = array_diff($search_words, array_flip($words_data));
			}

			return [$words_data, $rest_words];
		}

		return array();
	}
}
