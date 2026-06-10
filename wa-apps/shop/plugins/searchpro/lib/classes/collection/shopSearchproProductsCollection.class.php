<?php

class shopSearchproProductsCollection extends shopProductsCollection
{
	protected static $env;

	private $shop_category_model;
	private $index_instance;

	public function __construct($hash = '', array $options = array())
	{
		parent::__construct($hash, $options);
	}

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

	/**
	 * @return shopSearchproEnv
	 */
	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopSearchproEnv();

		return self::$env;
	}

	/**
	 * @return shopCategoryModel
	 */
	private function getShopCategoryModel()
	{
		if(!isset($this->shop_category_model)) {
			$this->shop_category_model = new shopCategoryModel();
		}

		return $this->shop_category_model;
	}

	/**
	 * @return shopSearchproIndex
	 */
	private function getIndexInstance()
	{
		if(!isset($this->index_instance)) {
			$options = array(
				'mode' => $this->getMode(),
				'word_forms' => !empty($this->options['word_forms']),
				'form_break_symbols' => ifempty($this->options, 'form_break_symbols', ''),
				'form_numbers' => !empty($this->options['form_numbers']),
				'form_strnum' => !empty($this->options['form_strnum']),
				'form_ignore_numstart' => !empty($this->options['form_ignore_numstart']),
				'form_min_length' => (int) ifempty($this->options, 'form_min_length', 1)
			);

			$this->index_instance = new shopSearchproIndex($options);
		}

		return $this->index_instance;
	}

	/**
	 * @param string $q
	 * @param string $where
	 */
	private function addSearchFieldsToQuery($q, &$where)
	{
		if(!empty($this->options['search_fields']['products'])) {
			$where = '(' . $where;

			if(!empty($this->options['search_fields']['products']['pages'])) {
				/**
				 * Поиск по подстраницам
				 */

				$this->joins[] = array(
					'type' => 'LEFT',
					'table' => 'shop_product_pages',
					'alias' => 'pp0'
				);

				$where .= " OR pp0.name LIKE '%" . $q . "%' OR pp0.content LIKE '%" . $q . "%'";
			}

			if(!empty($this->options['search_fields']['products']['seopage_plugin'])) {
				/**
				 * Поиск по подстраницам плагина SEO-страницы
				 */

				if($this->getEnv()->isEnabledSeopagePlugin() && class_exists('shopSeopagePageContentModel') && class_exists('shopSeopagePluginProducts')) {
					$seopage_plugin_page_model = new shopSeopagePageModel();
					$seopage_plugin_page_content_model = new shopSeopagePageContentModel();
					$result_pages = $seopage_plugin_page_model->query("SELECT p.id FROM {$seopage_plugin_page_model->getTableName()} AS p LEFT JOIN {$seopage_plugin_page_content_model->getTableName()} AS c ON p.id = c.id WHERE p.status = 1 AND (c.content LIKE '%" . $q ."%' OR c.name LIKE '%" . $q ."%')")->fetchAll('id');

					$page_ids = array_keys($result_pages);

					if(!empty($page_ids)) {
						$product_ids = array();

						foreach($page_ids as $page_id) {
							$seopage_plugin_products = new shopSeopagePluginProducts($page_id);
							$page_product_ids = $seopage_plugin_products->findProducts(true);

							$product_ids = array_merge($product_ids, $page_product_ids);

							//$this->addResultsToTheEnd($product_ids);
						}

						if(!empty($product_ids)) {
							$where .= " OR p.id IN (" . implode(',', $product_ids) . ")";
						}
					}
				}
			}

			$where .= ')';
		}
	}

	private function addSearchInCategoryId()
	{
		if(!empty($this->options['search_in_category_id'])) {
			$category_id = $this->options['search_in_category_id'];
			$category = $this->getShopCategoryModel()->getById($category_id);

			if($category['type'] == shopCategoryModel::TYPE_STATIC) {
				if($category['include_sub_categories']) {
					$tree = $this->getShopCategoryModel()->getTree($category_id);
					$category_ids = array_keys($tree);
					if(empty($category_ids))
						$category_ids[] = $category_id;

					$this->addJoin('shop_category_products', ':table.product_id = p.id', ':table.category_id IN (' . implode(',', $category_ids) . ')');
				} else {
					$this->addJoin('shop_category_products', ':table.product_id = p.id', ':table.category_id = ' . $category_id);
				}
			}
		}
	}

	private function clearJoins()
	{
		$joins = $this->joins;

		$this->joins = array();

		if(is_array($joins)) {
			foreach($joins as $key => $join) {
				if(substr($key, 0, strlen('ssp')) === 'ssp') {
					$this->joins[$key] = $join;
				}
			}
		}
	}

	protected function searchPrepare($query, $auto_title = true)
	{
		$method_name = $this->methodName('searchPrepare');

		return $this->$method_name($query, $auto_title);
	}

	private function defaultOrderBy($word_ids)
	{
		if(empty($this->fields['order_by'])) {
			if(count($word_ids) > 1) {
				$this->fields['order_by'] = "SUM(si.weight) AS weight";
				$this->fields['order_by_2'] = "COUNT(*) AS weight_count";
				$this->order_by = 'weight_count DESC, weight DESC';
				$this->group_by = 'p.id';
			} else {
				$this->fields['order_by'] = "si.weight";
				$this->order_by = 'si.weight DESC';
			}
		} elseif(count($word_ids) > 1) {
			$this->group_by = 'p.id';
		}
	}

	private function addWhereWordsIncludes($words)
	{
		if(empty($words)) {
			return;
		}

		foreach($words as $word) {
			$word_escaped = $this->getModel()->escape($word, 'like');

			$this->addWhereIncludes($word_escaped);
		}

		$this->group_by = 'p.id';
	}

	private function addWhereIncludes($word, $like_type = 2, $type = null)
	{
		if($like_type === 0) {
			$word_like = "{$word}%";
		} elseif($like_type === 1) {
			$word_like = "%{$word}";
		} else {
			$word_like = "%{$word}%";
		}

		$where_word = "(p.name LIKE '{$word_like}' OR :table.name LIKE '{$word_like}' OR :table.sku LIKE '{$word_like}')";

		/**
		 * Поиск PRO
		 * Дополняем запрос для поиска по дополнительным параметрам
		 */
		$this->addSearchFieldsToQuery($word, $where_word);

		$join = array(
			'table' => 'shop_product_skus',
			'where' => $where_word,
			'type' => $type
		);

		$this->addJoin($join);
	}

	protected function plugin_searchPrepare($query, $auto_title = true)
	{
		$query = urldecode($query);
		$query = mb_substr($query, strlen('query='));

		$model = $this->getModel();
		$title = array();

		/**
		 * Поиск PRO
		 * Дополняем запрос поиском внутри указанной категории
		 */
		$this->addSearchInCategoryId();

		$auto_order_by = $this->order_by;
		$auto_fields = $this->fields;

		list($word_ids, $rest_words, $word_masks) = $this->getIndexInstance()->getWordIds($query, true);

		$q = $model->escape($query, 'like');

		$is_rest_words = !empty($this->options['rest_words']) && !empty($rest_words);
		$is_have_wheres = !empty($word_ids) || $is_rest_words;

		if($is_have_wheres) {
			if($word_ids) {
				$this->joins[] = array(
					'table' => 'shop_search_index',
					'alias' => 'si'
				);

				$where = 'si.word_id IN (' . implode(",", $word_ids) . ')';

				$this->addSearchFieldsToQuery($q, $where);

				$this->where[] = $where;

				$this->defaultOrderBy($word_ids);
			}

			if($is_rest_words) {
				$this->addWhereWordsIncludes($rest_words);
			}
		} else {
			$this->where[] = '0';
		}

		$this->prepared = true;

		if(!$this->count()) {
			$this->count = null;

			/**
			 * Поиск PRO
			 * Очищаем joins
			 */
			$this->clearJoins();
			$this->where = $this->having = array();

			$this->fields = $auto_fields;
			if($this->is_frontend) {
				if($this->filtered) {
					$this->filtered = false;
				}
				$this->frontendConditions();
			}
			if(waRequest::request('sort', 'weight', 'string') == 'weight') {
				$this->order_by = 'p.create_datetime DESC';
			} else {
				$this->order_by = $auto_order_by;
			}

			$this->addWhereIncludes($q);

			/**
			 * Поиск PRO
			 * Дополняем запрос поиском внутри указанной категории
			 */
			$this->addSearchInCategoryId();

			$this->group_by = 'p.id';

			return;
		}
	}

	protected function shop_searchPrepare($query, $auto_title = true)
	{
		$query = urldecode($query);
		$i = $offset = 0;
		$query_parts = array();
		while(($j = strpos($query, '&', $offset)) !== false) {
			// escaped &
			if($query[$j - 1] != '\\') {
				$query_parts[] = str_replace('\&', '&', substr($query, $i, $j - $i));
				$i = $j + 1;
			}
			$offset = $j + 1;
		}
		$query_parts[] = str_replace('\&', '&', substr($query, $i));

		$model = $this->getModel();
		$title = array();

		foreach($query_parts as $part) {
			if(!($part = trim($part))) {
				continue;
			}
			$parts = preg_split("/(\\\$=|\^=|\*=|==|!=|>=|<=|=|>|<)/uis", $part, 2, PREG_SPLIT_DELIM_CAPTURE);
			if($parts) {
				if($parts[0] == 'category_id') {
					if($parts[1] == '==' && $parts[2] == 'null') {
						$this->where[] = 'p.category_id IS NULL';
						$title[] = 'without category';
					} else {
						$this->addJoin('shop_category_products', null, ':table.category_id' . $this->getExpression($parts[1], $parts[2]));
						$title[] = "category_id " . $parts[1] . $parts[2];
					}
				} elseif($parts[0] == 'query') {
					/**
					 * Поиск PRO
					 * Дополняем запрос поиском внутри указанной категории
					 */
					$this->addSearchInCategoryId();

					if(!wa('shop')->getConfig()->getOption('search_smart')) {
						// simple search

						$words = explode(' ', $parts[2]);
						$alias = $this->addJoin('shop_product_skus');
						foreach($words as $w) {
							$w = trim($w);
							$w = $model->escape($w, 'like');
							$this->where[] = "(p.name LIKE '%" . $w . "%' OR " . $alias . ".sku LIKE '%" . $w . "%' OR " . $alias . ".name LIKE '%" . $w . "%')";
						}
					} else {
						//smart search

						// Following block of code might change the ordering
						// as part of searching logic.
						// Remember order-by in case we want to restore it later.
						$auto_order_by = $this->order_by;
						$auto_fields = $this->fields; // save fields

						$word_ids = $this->getIndexInstance()->getWordIds($parts[2], true);

						$q = $model->escape($parts[2], 'like');

						if($word_ids) {
							$this->joins[] = array(
								'table' => 'shop_search_index',
								'alias' => 'si'
							);

							$where = 'si.word_id IN (' . implode(",", $word_ids) . ')';

							/**
							 * Поиск PRO
							 * Дополняем запрос для поиска по дополнительным параметрам
							 */
							$this->addSearchFieldsToQuery($q, $where);

							$this->where[] = $where;

							$this->defaultOrderBy($word_ids);
						} elseif($parts[2]) {
							$this->where[] = '0';
						}

						$this->prepared = true;
						// if not found try find by name
						if(!$this->count()) {
							$this->count = null;
							/**
							 * Поиск PRO
							 * Очищаем joins
							 */
							$this->clearJoins();
							$this->where = $this->having = array();

							$this->fields = $auto_fields; //restore fields;
							if($this->is_frontend) {
								if($this->filtered) {
									$this->filtered = false;
								}
								$this->frontendConditions();
							}
							if(waRequest::request('sort', 'weight', 'string') == 'weight') {
								$this->order_by = 'p.create_datetime DESC';
							} else {
								$this->order_by = $auto_order_by;
							}

							$where = "(p.name LIKE '%" . $q . "%' OR :table.name LIKE '%" . $q . "%' OR :table.sku LIKE '%" . $q . "%')";

							/**
							 * Поиск PRO
							 * Дополняем запрос для поиска по дополнительным параметрам
							 */
							$this->addSearchFieldsToQuery($q, $where);

							/**
							 * Поиск PRO
							 * Дополняем запрос поиском внутри указанной категории
							 */
							$this->addSearchInCategoryId();

							$this->addJoin('shop_product_skus', null, $where);
							$this->group_by = 'p.id';

							return;
						}

						// Restore original order-by if were specified.
						if (waRequest::request('sort', 'weight', 'string') != 'weight') {
							$this->order_by = $auto_order_by;
						}
						else {
							$this->order_by = $auto_order_by;
						}
					}
					$title[] = $parts[0] . $parts[1] . $parts[2];
				} elseif($parts[0] == 'tag') {
					$tag_model = $this->getModel('tag');
					/**
					 * @var shopTagModel $tag_model
					 */
					if(strpos($parts[2], '||') !== false) {
						$tags = explode('||', $parts[2]);
						$tag_ids = $tag_model->getIds($tags);
					} else {
						$sql = "SELECT id FROM " . $tag_model->getTableName();
						$sql .= " WHERE name" . $this->getExpression($parts[1], $parts[2]);
						$tag_ids = $tag_model->query($sql)->fetchAll(null, true);
					}
					if($tag_ids) {
						$this->addJoin('shop_product_tags', null, ":table.tag_id IN ('" . implode("', '", $tag_ids) . "')");
					} else {
						$this->where[] = "0";
					}
				} elseif($model->fieldExists($parts[0])) {
					$title[] = $parts[0] . $parts[1] . $parts[2];
					if($parts[0] === 'count' && in_array($parts[1], array('>', '>='))) {
						$this->where[] = '(p.' . $parts[0] . $this->getExpression($parts[1], $parts[2]) . ' OR p.count IS NULL)';
					} else {
						$this->where[] = 'p.' . $parts[0] . $this->getExpression($parts[1], $parts[2]);
					}
				} elseif($parts[1] == '=') {
					$code = $parts[0];
					$is_value_id = false;
					if(substr($code, -9) == '.value_id') {
						$code = substr($code, 0, -9);
						$is_value_id = true;
					}
					$feature_model = $this->getModel('feature');
					/**
					 * @var shopFeatureModel $feature_model
					 */
					$f = $feature_model->getByCode($code);
					if($f) {
						if($is_value_id) {
							$value_id = $parts[2];
						} else {
							$values_model = $feature_model->getValuesModel($f['type']);
							$value_id = $values_model->getValueId($f['id'], $parts[2]);
						}
						$this->addJoin('shop_product_features', null, ':table.feature_id = ' . $f['id'] . ' AND :table.feature_value_id = ' . (int)$value_id);
						$this->filtered_by_features[$f['id']] = array($value_id);
						$this->group_by = 'p.id';
					}
				}
			}
		}
		if($title) {
			$title = implode(', ', $title);
			// Strip slashes from search title.
			$bs = '\\\\';
			$title = preg_replace("~{$bs}(_|%|&|{$bs})~", '\1', $title);
		}
		if($auto_title) {
			$this->addTitle($title, ' ');
		}
	}

	public function getInitialProducts()
	{
		$this->getFields('*'); // todo протестировать

		$from_and_where = $this->getSQL();

		$sql = "SELECT *\n";
		$sql .= $from_and_where;

		$data = $this->getModel()->query($sql)->fetchAll('id');
		if(!$data) {
			return array();
		}

		return $data;
	}

	public function getProductsFilled($limit = null, $is_event_frontend_products = true)
	{
		$this->is_frontend = true;

		$joins = array(
			array(
				'type' => 'LEFT',
				'table' => 'shop_product_skus',
				'alias' => 'product_sku',
				'on' => 'p.sku_id = :table.id',
				'fields' => ':table.price AS sku_price, :table.compare_price AS sku_compare_price, :table.sku AS sku_sku'
			),
			array(
				'type' => 'LEFT',
				'table' => 'shop_category',
				'alias' => 'searchpro_sc',
				'on' => 'p.category_id = :table.id',
				'fields' => ':table.name AS category_name,:table.url AS category_url,:table.full_url AS category_full_url'
			)
		);

		$products = $this->getProducts('*,skus_filtered', 0, $limit, array(
			'escape' => true,
			'joins' => $joins
		));

		if($is_event_frontend_products) {
			wa('shop')->event('frontend_products', ref(array(
				'products' => &$products,
			)));
		}

		return $products;
	}

	public function getProductsInitial($fields = '*', $offset = 0, $limit = null, $escape = true)
	{
		return parent::getProducts($fields, $offset, $limit, $escape);
	}

	public function getProductIds($limit = null)
	{
		$products = $this->getProducts('id, name', 0, $limit);

		return $products;
	}

	public function getProducts($fields = '*', $offset = 0, $limit = null, $escape = true)
	{
		$method_name = $this->methodName('getProducts');

		return $this->$method_name($fields, $offset, $limit, $escape);
	}

	public function shop_getProducts($fields = '*', $offset = 0, $limit = null, $escape = true)
	{
		$extra_params = array();

		if(is_array($escape)) {
			$extra_params = $escape;

			$escape = $extra_params['escape'];
		}

		if(is_bool($limit)) {
			$escape = $limit;
			$limit = null;
		}

		if($limit === null) {
			if($offset) {
				$limit = $offset;
				$offset = 0;
			}
		}

		if($this->hash[0] == 'set' && !empty($this->info['id']) && $this->info['type'] == shopSetModel::TYPE_DYNAMIC) {
			$this->count();
			if($offset + $limit > $this->count) {
				$limit = $this->count - $offset;
			}
		}

		$distinct = $this->joins && !$this->group_by ? 'DISTINCT ' : '';

		if(array_key_exists('joins', $extra_params)) {
			$sql_additional_fields = array();

			foreach($extra_params['joins'] as $join_id => $join) {
				$alias = "sspc$join_id";

				$join['alias'] = $alias;
				$join['on'] = str_replace(':table', $alias, $join['on']);
				$sql_additional_fields[$alias] = str_replace(':table', $alias, $join['fields']);

				$this->joins[$alias] = $join;
			}
		}

		if ($this->getMode() == 'plugin')
		{
			$from_and_where = $this->getSQL();
			$sql_fields = $this->getFields($fields);
		}
		else
		{
			$sql_fields = $this->getFields($fields);

			$sort = waRequest::request('sort');
			if (is_string($sql_fields) && ($sort == 'count' || $sort == 'stock'))
			{
				$sql_fields = trim($sql_fields) === ''
					? 'IF(p.count IS NULL, 1, 0) count_null'
					: $sql_fields . ', IF(p.count IS NULL, 1, 0) count_null';
			}
			$from_and_where = $this->getSQL();
		}

		if(!empty($sql_additional_fields)) {
			foreach($sql_additional_fields as $sql_additional_field) {
				$sql_fields .= ",{$sql_additional_field}";
			}
		}

		$sql = "SELECT " . $distinct . $sql_fields . "\n";

		$sql .= $from_and_where;
		$sql .= "\nGROUP BY p.id";
		if($this->having) {
			$sql .= "\nHAVING " . implode(' AND ', $this->having);
		}
		$sql .= $this->_getOrderBy();

		if($limit !== null) {
			$sql .= "\nLIMIT " . ($offset ? $offset . ',' : '') . (int)$limit;
		}

		$data = $this->getModel()->query($sql)->fetchAll('id');

		if(!$data) {
			return array();
		}

		return $data;
	}
}
