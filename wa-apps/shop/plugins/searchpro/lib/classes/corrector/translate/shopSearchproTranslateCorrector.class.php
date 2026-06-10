<?php

/**
 * Класс для исправления английских слов, введенных на русском
 */
class shopSearchproTranslateCorrector extends shopSearchproCorrector
{
	private $corrector_storage;

	public function __construct($params = array())
	{
		parent::__construct($params);

		$this->corrector_storage = new shopSearchproCorrectorStorage('Translate');
	}

	private function getCorrectorStorage()
	{
		return $this->corrector_storage;
	}

	private function getData($name = null, $default = null)
	{
		return $this->getCorrectorStorage()->getData($name, $default);
	}

	private function checkForChar($condition, $char) {
		switch($condition) {
			case 'vowel':
				return preg_match('/[аеёиоуыэюя]/', $char);
				break;
			case 'consonant':
				return preg_match('/[^аеёиоуыэюя]/', $char);
				break;
			default:
				return $char == $condition;
				break;
		}
	}

	/**
	 * Исправляет русское слово на его английское представление
	 * @param string $word
	 * @param array $options
	 */
	public function fixWord(&$word, $options = array())
	{
		if(preg_match('/^[а-яё]+$/u', $word)) {
			$parts = $this->getData('parts');
			$table = $this->getData('table');

			$output = '';

			foreach($parts as $part => $replace_with) {
				$word = preg_replace("/({$part})/", $replace_with, $word);
			}

			$chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);

			foreach($chars as $i => $char) {
				if(array_key_exists($char, $table)) {
					$replace = $table[$char];

					if(is_array($replace)) {
						$else = $replace['else'];
						unset($replace['else']);
						$done = false;
						foreach($replace as $rule => $rule_value) {
							switch($rule) {
								case 'previous_is':
									if(isset($chars[$i - 1])) {
										foreach($rule_value as $if_is => $replace_with) {
											if($this->checkForChar($if_is, $chars[$i - 1])) {
												$done = true;
												$output .= $replace_with;
												break 3;
											}
										}
									}
									break;
								case 'next_is':
									if(isset($chars[$i + 1])) {
										foreach($rule_value as $if_is => $replace_with) {
											if($this->checkForChar($if_is, $chars[$i + 1])) {
												$done = true;
												$output .= $replace_with;
												break 3;
											}
										}
									}
									break;
								case 'is_first':
									if($i == 0) {
										$done = true;
										$output .= $rule_value;
									}
									break;
								case 'is_last':
									if($i == count($chars) - 1) {
										$done = true;
										$output .= $rule_value;
									}
									break;
								case 'first_is':
									foreach($rule_value as $if_is => $replace_with) {
										if($this->checkForChar($if_is, $chars[0])) {
											$done = true;
											$output .= $replace_with;
											break 3;
										}
									}
									break;
							}
						}

						if(!$done)
							$output .= $else;
					} else {
						$output .= $replace;
					}
				} else {
					$output .= $char;
				}
			}

			$word = $output;
		}
	}
}