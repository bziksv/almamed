<?php

class shopSearchproFinder
{
	const CACHE_ACTUALITY = 86400;

	private $params;
	private $counts = array();
	private $results_within_query = array();
	private $correctors = array();
	private $finders = array();
	private $collections = array();
	private $output_collections = array();
	private $relevancy_multipliers = array(
		'General' => 1,

		'KeyboardLayout' => 0.9,

		'Grams' => 0.8,
		'Translate+Grams' => 0.75,

		'Translate' => 0.7,
		'KeyboardLayout+Translate+Grams' => 0.6,
		'KeyboardLayout+Grams' => 0.5
	);
	private $search_entities = array(
		'categories', 'brands', 'history', 'popular'
	);

	public $current_word;
	public $current_query;

	public function __construct($params = array())
	{
		$this->params = $params;
	}

	private function getCacheKey($word)
	{
		$hash = md5($word . '-' . $this->getCacheType() . json_encode($this->getFinderParams()));
		$key = "shop_searchpro_results_{$hash}";

		return $key;
	}

	private function getCacheType()
	{
		return $this->getParam('cache_type');
	}

	private function getCacheActuality()
	{
		return (int) $this->getParam('cache_actuality');
	}

	protected function getCache($word)
	{
		return new waSerializeCache($this->getCacheKey($word), $this->getCacheActuality());
	}

	private function get($type, $word)
	{
		if($this->getCacheActuality() === 0) {
			return null;
		}

		if(!in_array($type, array('products', 'categories'))) {
			// Кешируются только результаты поиска по товарам и категориям
			return null;
		}

		$cache = $this->getCache($word)->get();

		if($cache === null) {
			return null;
		}

		$result = ifset($cache, $type, null);

		if(!$result) {
			return array();
		}

		$results = $this->getResultsByKeys($type, $result);

		return $results;
	}

	private function save($type, $word, $results)
	{
		if($this->getCacheActuality() === 0) {
			return null;
		}

		if(!in_array($type, array('products', 'categories'))) {
			return null;
		}

		$results = array_keys($results);

		$cache = $this->getCache($word)->get();

		if(!$cache) {
			$cache = array();
		}

		$cache[$type] = $results;

		return $this->getCache($word)->set($cache);
	}

	private function increaseCount($type, $length)
	{
		if(!array_key_exists($type, $this->counts)) {
			$this->counts[$type] = 0;
		}

		$this->counts[$type] = $this->counts[$type] + $length;
	}

	public function getCount($type)
	{
		return ifset($this->counts, $type, 0);
	}

	/**
	 * @param string $type
	 * @return mixed
	 * @throws waException
	 */
	protected function getCorrector($type = 'default')
	{
		if(!isset($this->correctors[$type])) {
			if($type === 'default') {
				$class = 'shopSearchproCorrector';
			} else {
				$class = "shopSearchpro{$type}Corrector";
			}

			if(class_exists($class)) {
				$corrector = new $class($this->getParams());
			} else {
				throw new waException("Неизвестный класс корректора \"$class\"");
			}

			$this->correctors[$type] = $corrector;
		}

		return $this->correctors[$type];
	}

	/**
	 * @return shopSearchproGeneralCorrector
	 * @throws waException
	 */
	protected function getGeneralCorrector()
	{
		return $this->getCorrector('General');
	}

	/**
	 * @return shopSearchproGramsCorrector
	 * @throws waException
	 */
	protected function getGramsCorrector()
	{
		return $this->getCorrector('Grams');
	}

	/**
	 * @return shopSearchproKeyboardLayoutCorrector
	 * @throws waException
	 */
	protected function getKeyboardLayoutCorrector()
	{
		return $this->getCorrector('KeyboardLayout');
	}

	/**
	 * @return shopSearchproKeyboardLayoutSmartCorrector
	 * @throws waException
	 */
	protected function getKeyboardLayoutSmartCorrector()
	{
		return $this->getCorrector('KeyboardLayoutSmart');
	}

	/**
	 * @return shopSearchproKeyboardLayoutSmartCyrillicCorrector
	 * @throws waException
	 */
	protected function getKeyboardLayoutSmartCyrillicCorrector()
	{
		return $this->getCorrector('KeyboardLayoutSmartCyrillic');
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

	private function getLimitCounts()
	{
		$counts = $this->getParam('counts');

		if(!is_array($counts) || empty($counts)) {
			return array();
		}

		return $counts;
	}

	private function getLimitCount($entity, $type = 'max')
	{
		$counts = $this->getLimitCounts();
		$is_entity_in_counts = array_key_exists($entity, $counts);

		if($is_entity_in_counts) {
			$entity_counts = $counts[$entity];
			$is_type_in_counts = array_key_exists($type, $entity_counts);

			if($is_type_in_counts) {
				$limit = (int) $entity_counts[$type];

				return $limit > 0 ? $limit : null;
			}
		}

		return null;
	}

	private function getMinCount($entity)
	{
		return $this->getLimitCount($entity, 'min');
	}

	private function getMaxCount($entity)
	{
		return $this->getLimitCount($entity, 'max');
	}

	private function getFinderParams()
	{
		return array(
			'mode' => $this->getParam('mode'),
			'match_status' => $this->getParam('match_status'),
			'search_fields' => $this->getParam('fields'),
			'search_in_category_id' => $this->getParam('category_id'),
			'brands_plugin' => $this->getParam('brands_plugin'),
			'rest_words' => $this->getParam('rest_words'),
			'word_forms' => $this->getParam('word_forms'),
			'form_break_symbols' => $this->getParam('form_break_symbols'),
			'form_numbers' => $this->getParam('form_numbers'),
			'form_strnum' => $this->getParam('form_strnum'),
			'form_ignore_numstart' => $this->getParam('form_ignore_numstart'),
			'form_min_length' => $this->getParam('form_min_length')
		);
	}

	private function getCollection($type, $query)
	{
		$hash = null;

		switch($type) {
			case 'search':
				$query = str_replace('&', '\&', $query);
				$hash = "search/query=$query";
				break;
			case 'ids':
				$hash = $query;
				break;
		}

		if($hash) {
			$hash_key = $hash;
			if(is_array($hash_key)) {
				$hash_key = md5(json_encode($hash_key));
			}

			if(!isset($this->collections[$hash_key])) {
				$this->collections[$hash_key] = new shopSearchproProductsCollection($hash, $this->getFinderParams());
			}

			return $this->collections[$hash_key];
		}

		return null;
	}

	/**
	 * @param string $type
	 * @return mixed
	 * @throws waException
	 */
	private function getFinder($type)
	{
		if(!isset($this->finders[$type])) {
			$type_name = ucfirst($type);
			$class = "shopSearchpro{$type_name}Finder";

			if(class_exists($class)) {
				$params = $this->getFinderParams();
				$this->finders[$type] = new $class($params);
			} else {
				throw new waException("Неизвестный класс поиска \"$class\"");
			}
		}

		return $this->finders[$type];
	}

	private function createOutputCollection($type, $results)
	{
		$this->output_collections[$type] = new shopSearchproProductsCollection(array_keys($results));

		return $this->output_collections[$type];
	}

	public function getOutputCollection($type)
	{
		if(array_key_exists($type, $this->output_collections)) {
			return $this->output_collections[$type];
		}

		return null;
	}

	private function sortProducts(&$results)
	{
		$sort_body = "return \$b['relevancy'] - \$a['relevancy']";

		if(waRequest::param('drop_out_of_stock') == 1) {
			$sort_body .= " + (isset(\$b['in_stock']) && isset(\$a['in_stock']) ? (\$b['in_stock'] - \$a['in_stock']) : 0)";
		}

		uasort($results, wa_lambda('$a, $b', $sort_body . ';'));
	}

	private function saveOutputResults($type, &$results)
	{
		if($type === 'products') {
			$this->sortProducts($results);
		}

		if($limit = $this->getResultsCount($type, 'max'))
			$results = array_slice($results, 0, $limit);

		/*$key_filled_results = array();

		foreach($results as $result)
			$key_filled_results[$result['id']] = $result;

		$results = $key_filled_results;*/
	}

	/**
	 * Проверяет, нужно ли продолжить поиск с корректором
	 * @param string $type
	 * @param array $results
	 * @return bool
	 */
	private function isContinueWithinCorrector($type, $results)
	{
		switch($this->getParam('corrector_status')) {
			case 'logical':
				return !$results;
				break;
			case 'merging':
				$merging_count = $this->getMergingCount($type, $results);
				return $merging_count > 0;
				break;
		}

		return false;
	}

	/**
	 * Возвращает максимальное количество результатов поиска по конкретной сущности
	 * @param string $type
	 * @param array $results
	 * @return int|null
	 */
	private function getResultsLimit($type, $results)
	{
		switch($this->getParam('corrector_status')) {
			case 'logical':
				return null;
				break;
			case 'merging':
				return $this->getMergingCount($type, $results);
				break;
		}

		return null;
	}

	/**
	 * Получает количество сущностей, которые нужно добавить к результатам поиска до минимального количества
	 * @param string $type
	 * @param array $results
	 * @return int
	 */
	private function getMergingCount($type, $results)
	{
		$results_count = $this->getResultsCount($type, 'min');

		return $results_count > 0 ? $results_count - count($results) : 0;
	}

	/**
	 * Получает минимальное или максимальное количество сущностей, которое необходимо получить
	 * @param string $type
	 * @param string $state (min|max)
	 * @return int
	 */
	private function getResultsCount($type, $state)
	{
		$counts = $this->getParam('counts');

		if(isset($counts[$type][$state])) {
			return (int) $counts[$type][$state];
		}

		return 0;
	}

	/**
	 * Запоминает по какому именно запроса была найдена сущность для дальнейшего выделения ключевого слова в результатах поиска
	 * @param string $query
	 * @param string $type
	 * @param array $results
	 */
	private function pushResultsWithinQuery($query, $type, $results)
	{
		foreach($results as $id => $result) {
			if(!isset($this->results_within_query[$type]))
				$this->results_within_query[$type] = array();

			if(!isset($this->results_within_query[$type][$result['id']]))
				$this->results_within_query[$type][$result['id']] = array();

			$this->results_within_query[$type][$result['id']][] = $query;
		}
	}

	/**
	 * Возвращает ключевое слово, по которому была найдена конкретная сущность
	 * @param string $type
	 * @param int $id
	 * @return array|null
	 */
	public function getQueryForResultElement($type, $id)
	{
		if(array_key_exists($type, $this->results_within_query) && array_key_exists($id, $this->results_within_query[$type])) {
			return $this->results_within_query[$type][$id];
		}

		return null;
	}

	private function saveResults(&$results, $corrected_results)
	{
		$results = shopSearchproUtil::replace($results, $corrected_results);
	}

	private function getResultsByKeys($type, $result_keys)
	{
		$results = null;

		if($type === 'products') {
			$results = $this->searchProducts($result_keys);
		} elseif($type === 'categories') {
			$results = $this->searchEntities($type, $result_keys);
		}

		return $results;
	}

	/**
	 * Ищет товары по запросу
	 * @param string|array $query
	 * @param int|null $limit
	 * @return array
	 */
	private function searchProducts($query, $limit = null)
	{
		if(is_array($query)) {
			$collection = $this->getCollection('ids', $query);
		} else {
			$collection = $this->getCollection('search', $query);
		}

		if($collection) {
			$finder_params = $this->getFinderParams();
			$search_fields = $finder_params['search_fields'];

			$products = $collection->getProductIds($limit);

			/*if(!empty($search_fields['products']['filled'])) {
				$is_event_frontend_products = !empty($search_fields['products']['event_frontend_products']);
				$products = $collection->getProductsFilled($limit, $is_event_frontend_products);
			} else {
				$products = $collection->getProductIds($limit);
			}*/

			return $products;
		}

		return array();
	}

	private function searchEntities($type, $query, $limit = null)
	{
		$finder = $this->getFinder($type);

		if($finder) {
			$categories = $finder->findEntities($query, $limit);

			return $categories;
		}

		return array();
	}

	/**
	 * Ищет сущности по запросу
	 * @param string $type
	 * @param string|array $query
	 * @param string $corrector
	 * @return array
	 * @throws shopSearchproException
	 * @throws waException
	 */
	private function search($type, $query, $corrector = null)
	{
		$results = $this->get($type, $query);

		if(!$results) {
			if($type === 'products') {
				$results = $this->searchProducts($query);
			} elseif(in_array($type, $this->search_entities)) {
				$results = $this->searchEntities($type, $query);
			} else {
				throw new shopSearchproException('UNKNOWN_SEARCH_TYPE');
			}

			$this->save($type, $query, $results);
		}

		$this->checkForRelevance($results, $query, $corrector);

		return $results;
	}

	private function getRelevancyMultiplier($corrector)
	{
		return ifset($this->relevancy_multipliers, $corrector, 0);
	}

	private function checkForRelevance(&$results, $query, $corrector = null)
	{
		$compare_query = $this->current_word;
		$full_query = $this->current_query;

		$query_language = shopSearchproKeyboardLayoutCorrector::getLanguage($query);
		$source_query_language = shopSearchproKeyboardLayoutCorrector::getLanguage($this->current_word);
		if($query_language != $source_query_language) {
			$compare_query = shopSearchproKeyboardLayoutCorrector::convert($compare_query, "$source_query_language-$query_language");
		}

		$preg_quoted_query = preg_quote($query, '/');
		$preg_quoted_full_query = preg_quote($full_query, '/');

		if(preg_match("/([a-zA-Z]+)-?([0-9]+)/", $query, $matches)) {
			$q1 = "{$matches[1]} {$matches[2]}";
			$q2 = "{$matches[1]}-{$matches[2]}";

			$pqq1 = preg_quote($q1, '/');
			$pqq2 = preg_quote($q2, '/');

			if(strlen($matches[2]) >= 2) {
				$pqq3 = preg_quote($matches[2], '/');
			}

			$preg_quoted_query .= "|{$pqq1}|{$pqq2}";
		}

		if(preg_match("/({$preg_quoted_query})/iu", $compare_query)) {
			$relevancy_multiplier = 1;

			/**
			 * Совпадение полностью по строке
			 */
		} elseif(preg_match(sprintf("/([^a-zа-яё])(%s)/iu", preg_quote(mb_substr($query, 0, 2), '/')), ' ' . $compare_query) || preg_match(sprintf("/(%s)([^a-zа-яё])/iu", preg_quote(mb_substr($query, -2), '/')), $compare_query . ' ')) {
			/**
			 * Совпадение по 2 первым или 2 последним буквам
			 */
			$relevancy_multiplier = 0.4;
		} elseif(preg_match(sprintf("/([^a-zа-яё])(%s)/iu", preg_quote(mb_substr($query, 0, 1), '/')), ' ' . $compare_query) || preg_match(sprintf("/(%s)([^a-zа-яё])/iu", preg_quote(mb_substr($query, -1), '/')), $compare_query . ' ')) {
			/**
			 * Совпадение по 1 первой или 1 последней букве
			 */
			$relevancy_multiplier = 0.25;
		} else {
			$relevancy_multiplier = 0;
		}

		$params = array(
			'query' => $query,
			'preg_quoted_query' => $preg_quoted_query,
			'preg_quoted_full_query' => $preg_quoted_full_query,
			'relevancy_multiplier' => $relevancy_multiplier,
			'corrector' => $corrector
		);

		foreach($results as $key => &$result) {
			$this->checkResultForRelevancy($result, 'name', $params);
			$this->checkResultForRelevancy($result, 'summary', $params, true);
		}
	}

	private function checkResultForRelevancy(&$result, $key, array $params, $is_strong_key = false)
	{
		if(!array_key_exists('relevancy', $result)) {
			$result['relevancy'] = 0;
		}

		$query = $params['query'];
		$preg_quoted_query = $params['preg_quoted_query'];
		$preg_quoted_full_query = $params['preg_quoted_full_query'];
		$relevancy_multiplier = $params['relevancy_multiplier'];
		$corrector = $params['corrector'];

		if(array_key_exists($key, $result)) {
			$value = $result[$key];
		} else if(!$is_strong_key) {
			$value = json_encode($result);
		} else {
			return;
		}

		$relevancy = 0;

		if(preg_match("/{$preg_quoted_full_query}/iu", $value)) {
			if(preg_match("/^{$preg_quoted_full_query}$/iu", $value)) {
				$relevancy = 100;
			} elseif(preg_match("/{$preg_quoted_full_query}(?=\s|$)/iu", $value)) {
				$relevancy = 88;
			} else {
				$relevancy = 87;
			}
		} elseif(preg_match("/{$preg_quoted_query}/iu", $value)) {
			if(preg_match("/^{$preg_quoted_query}/iu", $value))
				$relevancy = 75;
			elseif(preg_match("/{$preg_quoted_query}(?=\s|$)/iu", $value))
				$relevancy = 74;
			else
				$relevancy = 74;
		} elseif(isset($pqq3) && preg_match("/{$pqq3}/", $value)) {
			$relevancy = 25;
		} else {
			if(preg_match('/^[а-яё]+$/iu', $query) && mb_strlen($query) >= 4) {
				$pattern = preg_quote(mb_substr($query, 0, -2), '/');
				$pattern .= mb_strlen($query) == 4 ? '.' : '..';
			} elseif(preg_match('/^[a-z]+$/', $query) && strlen($query) >= 4) {
				$pattern = $preg_quoted_query . '(?:es|s)?';
			}

			if(isset($pattern)) {
				if(preg_match("/($pattern)/iu", $value)) {
					$relevancy = 50;
				} else {
					$relevancy = 25;
				}
			}
		}

		$relevancy = $relevancy_multiplier * $relevancy * $this->getRelevancyMultiplier($corrector);

		$result['relevancy'] += $relevancy;
	}

	/**
	 * Поиск одних и тех же сущностей во всех переданных словах запроса
	 * @param string $type
	 * @param string $query
	 * @return shopSearchproResult
	 * @throws waException
	 */
	public function find($type, $query)
	{
		$collection = null;

		if(empty($query)) {
			return new shopSearchproResult(false);
		}

		$results = array();
		$step = 1;

		$this->current_query = $query;

		$is_slice_query = $type !== 'products' || ($type === 'products' && $this->getParam('mode') === 'shop' && !!$this->getParam('slice_query'));

		if(!$is_slice_query) {
			$words = array($query);
		} else {
			$words = shopSearchproPluginHelper::sliceQuery($query);
		}

		foreach($words as $word) {
			$word_results = $this->findWord($type, $word);

			if(empty($word_results)) {
				if(mb_strlen($word) < 3)
				{
					continue;
				}

				$results = array();
				break;
			}

			if(empty($results) || $step === 1) {
				$results = $word_results;
			} else {
				$results = array_intersect_key($results, $word_results);
			}

			$step++;
		}

		/*$limit = $this->getMaxCount($type);
		if($limit !== null) {
			$results = array_slice($results, 0, $limit);
		}*/

		if($results) {
			$this->increaseCount($type, count($results));
			$this->saveOutputResults($type, $results);

			if($type === 'products') {
				$collection = $this->createOutputCollection($type, $results);
			}
		}

		return new shopSearchproResult($results, ifset($collection));
	}

	/**
	 * Алгоритм поиска по сущностям и запросу до тех пор, пока не будет найден хотя бы один результат, либо до минимального количества сущностей в качестве результатов поиска
	 * @param string $type
	 * @param string $word
	 * @return array
	 * @throws waException
	 */
	protected function findWord($type, $word)
	{
		$this->current_word = $word;

		$results = array();

		$fixed_query = $this->getGeneralCorrector()->fixQuery($word);

		if($fixed_query) {
			$results = $this->search($type, $fixed_query, 'General');
			$this->pushResultsWithinQuery($fixed_query, $type, $results);
		}

		if($this->isContinueWithinCorrector($type, $results) && $this->getParam('keyboard_layout_status')) {
			try {
				$corrector = $this->getKeyboardLayoutCorrector();
				$corrector->setMode($this->getParam('keyboard_layout_mode'));
				$fixed_keyboard_layout_query = $corrector->fixQuery($word); // KeyboardLayout должна использовать все символы

				if($fixed_keyboard_layout_query) {
					$corrected_results = $this->search($type, $fixed_keyboard_layout_query, 'KeyboardLayout');
					$this->saveResults($results, $corrected_results);
					$this->pushResultsWithinQuery($fixed_keyboard_layout_query, $type, $results);
				}
			} catch(shopSearchproException $e) {
				// @todo: catch exception
			}
		}

		if($this->isContinueWithinCorrector($type, $results) && $this->getParam('grams_status')) {
			try {
				$fixed_grams_query = $this->getGramsCorrector()->fixQuery($fixed_query); // В отличие от Grams, которому не нужны посторонние символы

				if($fixed_grams_query) {
					$corrected_results = $this->search($type, $fixed_grams_query, 'Grams');
					$this->saveResults($results, $corrected_results);
					$this->pushResultsWithinQuery($fixed_grams_query, $type, $results);
				}
			} catch(shopSearchproException $e) {
				// @todo: catch exception
			}
		}

		/**
		 * Комбинированная обработка запроса (комбинирование корректоров)
		 */
		if($this->isContinueWithinCorrector($type, $results) && $this->getParam('combine_status')) {
			switch($this->getParam('combine_status')) {
				case 'KeyboardLayout+Grams':
					/**
					 * Исправление раскладки + исправление опечаток
					 */
					if($this->getParam('grams_status') && $this->getParam('keyboard_layout_status') && ($this->getParam('combine_status') === 'KeyboardLayout+Grams')) {
						try {
							if($this->getParam('keyboard_layout_mode') === 'smart') {
								$corrector = $this->getKeyboardLayoutCorrector();
								$corrector->getSmartCorrector()->setFindDifferentVariant();

								$fixed_keyboard_layout_query = $corrector->fixQuery($word);
							}

							$fixed_combine_query = $this->getGramsCorrector()->fixQuery($fixed_keyboard_layout_query, $this->getParam('grams_mode'));

							if($fixed_combine_query) {
								$corrected_results = $this->search($type, $fixed_combine_query, $this->getParam('combine_status'));
								$this->saveResults($results, $corrected_results);
								$this->pushResultsWithinQuery($fixed_combine_query, $type, $results);
							}
						} catch(shopSearchproException $e) {
							// @todo: catch exception
						}
					}

					break;
			}
		}

		return $results;
	}
}