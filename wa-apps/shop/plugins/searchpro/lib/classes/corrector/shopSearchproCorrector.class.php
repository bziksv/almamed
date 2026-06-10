<?php

class shopSearchproCorrector
{
	protected $params;

	public function __construct($params = array())
	{
		$this->params = $params;
	}

	protected function getParams()
	{
		return $this->params;
	}

	protected function getParam($name)
	{
		if(array_key_exists($name, $this->params)) {
			return $this->params[$name];
		}

		return null;
	}

	/**
	 * Разбивает строку на слова
	 * @param string $query
	 * @return array
	 */
	public function slice($query)
	{
		return explode(' ', $query);
	}

	/**
	 * Собирает слова в строку
	 * @param string $words
	 * @return string
	 */
	public function merge($words)
	{
		return implode(' ', $words);
	}

	/**
	 * Разбивает строку на слова, затем каждое из них пытается откорректировать
	 * @param string $query
	 * @param array $options
	 * @throws shopSearchproException
	 * @return string
	 */
	public function fixQuery($query, $options = array())
	{
		$words = $this->slice($query);

		if(!empty($words)) {
			foreach($words as &$word) {
				$word = trim($word);

				$this->fixWord($word, $options);
			}

			$result = $this->merge($words);

			return $result;
		} else {
			throw new shopSearchproException('EMPTY_QUERY');
		}
	}
}