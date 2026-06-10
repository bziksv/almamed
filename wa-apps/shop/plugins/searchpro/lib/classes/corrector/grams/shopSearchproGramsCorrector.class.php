<?php

/**
 * Класс для исправления опечаток в запросе
 */
class shopSearchproGramsCorrector extends shopSearchproCorrector
{
	private $grams_model;

	public function __construct($params = array())
	{
		parent::__construct($params);

		$this->grams_model = new shopSearchproGramsModel();
	}

	private function getGramsModel()
	{
		return $this->grams_model;
	}

	protected function getSearchType()
	{
		return $this->getParam('grams_mode');
	}

	/**
	 * Сверяет слово с N-граммами и, в случае удачи, исправляет исходное слово на слово без опечаток
	 * @param string $word
	 * @param array $options
	 */
	public function fixWord(&$word, $options = array())
	{
		$grams = shopSearchproPluginHelper::createGrams($word);

		$fixed_word = $this->getGramsModel()->getWord($word, $grams, $this->getSearchType());

		if(!empty($fixed_word)) {
			$word = $fixed_word;
		}
	}
}