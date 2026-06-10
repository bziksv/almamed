<?php

/**
 * Класс обновления индексов существующих N-грамм
 */
class shopSearchproGramsUpdater
{
	const MIN_WORD_LENGTH = 4;

	private $type;

	private $grams_model;
	private $entity_model;

	public function __construct($type)
	{
		$this->type = $type;

		$this->grams_model = new shopSearchproGramsModel();
	}

	private function getGramsModel()
	{
		return $this->grams_model;
	}

	private function getEntityModel()
	{
		if(!isset($this->entity_model)) {
			switch($this->type) {
				case 'products':
					$this->entity_model = new shopProductModel();
					break;
				case 'categories':
					$this->entity_model = new shopCategoryModel();
					break;
			}
		}

		return $this->entity_model;
	}

	/**
	 * Возвращает количество сущностей, по которым может быть произведен поиск
	 * @return int
	 */
	public function getEntityCount()
	{
		return $this->getEntityModel()->countAll();
	}

	/**
	 * Возвращает список сущностей в заданных лимитах, по которым может быть произведен поиск
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	private function getEntities($offset = 0, $limit = 500)
	{
		$entities = $this->getEntityModel()->select('name, description')->limit("$offset, $limit")->fetchAll();

		return $entities;
	}

	/**
	 * Обновляет N-граммы для сущностей в заданных лимитах
	 * @param int $offset
	 * @param int $limit
	 * @return int - Количество обработанных сущностей
	 */
	public function update($offset = 0, $limit = 500)
	{
		$entities = $this->getEntities($offset, $limit);

		$i = 0;
		foreach($entities as $entity) {
			foreach($entity as $field_type => $field) {
				$words = shopSearchproPluginHelper::sliceQuery($field);
				foreach($words as $word) {
					if(empty($word) || mb_strlen($word) < self::MIN_WORD_LENGTH) {
						continue;
					}

					$existing_grams_params = $this->getGramsModel()->getGrams($word);

					if($existing_grams_params) {
						$this->getGramsModel()->increaseGramsFrequency($word);
					} else {
						$grams = shopSearchproPluginHelper::createGrams($word);

						if($grams) {
							$this->getGramsModel()->addGrams($word, $grams, $this->type, $field_type);
						}
					}
				}
			}
			$i++;
		}

		return $i;
	}
}