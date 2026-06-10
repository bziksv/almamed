<?php

/**
 * Class shopSeofilterFilter
 * @property int $id
 * @property string $seo_name
 * @property string $url
 * @property string $seo_h1
 * @property string $seo_description
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property int $is_enabled
 * @property string $feature_value_hash
 * @property string $storefronts_use_mode
 * @property string $categories_use_mode
 * @property string $update_datetime
 * @property int $generator_process_id
 * @property int $feature_values_count
 * @property int $feature_value_ranges_count
 * @property string $default_product_sort
 * @property string $empty_page_http_code
 *
 * @property string[] $filter_storefronts
 * @property int[] $filter_categories
 * @property-read array $filter_category_names
 * @property-read shopSeofilterFilterFeatureValueActiveRecord[] $all_feature_values
 *
 * @property array $fields
 *
 * @method shopSeofilterFilterModel model()
 * @method shopSeofilterFilter|null getById($id)
 * @method shopSeofilterFilter[] getAllByFields($fields)
 *
 * relations
 * @property shopSeofilterFilterFeatureValue[] $featureValues
 * @property shopSeofilterFilterFeatureValueRange[] $featureValueRanges
 * @property shopSeofilterFilterPersonalRule[] $personalRules
 * @property shopSeofilterFilterCategory[] $categories
 * @property shopSeofilterFilterStorefront[] $storefronts
 * @property shopSeofilterFilterPersonalCanonical[] $canonicals
 */
class shopSeofilterFilter extends shopSeofilterActiveRecord
{
	const DISABLED = 0;
	const ENABLED = 1;

	const DEFAULT_SORT = 'seo_name';
	const DEFAULT_ORDER = 'asc';

	const TYPE_PRICE = 'price';

	const USE_MODE_ALL = 'ALL';
	const USE_MODE_LISTED = 'LISTED';
	const USE_MODE_EXCEPT = 'EXCEPT';

	const PARAMS_ALL_VALUES = 1;
	const PARAMS_WITHOUT_CURRENCY_VALUES = 2;
	const PARAMS_WITHOUT_RANGE_VALUES = 3;
	const PARAMS_WITH_STOREFRONT_CURRENCY = 4;

	const ERROR_KEY_FEATURE_VALUES = 'featureValues';

	private static $_category_ids = null;
	private static $_storefronts = null;

	private $_personal_template = array(
		'meta_title' => '',
		'meta_description' => '',
		'meta_keywords' => '',
		'seo_h1' => '',
		'seo_description' => '',
	);

	private $_active_personal_rule = array();

	/**
	 * загрузка из базы фильтров сразу с характеристиками - попытка уменьшить количество запросов для sitemap. пока лучше не стало :(
	 *
	 * @param $ids
	 * @return shopSeofilterFilter[]
	 */
	public function getByIdsWithParams($ids)
	{
		$sql = '
SELECT f.*, ff_v.code v_code, ff_v_r.code v_r_code, f_v.id f_v_id, f_v.feature_id f_v_feature_id, f_v.value_id f_v_value_id, f_v_r.id f_v_r_id, f_v_r.feature_id f_v_r_feature_id, f_v_r.`type` f_v_r_type, f_v_r.unit f_v_r_unit, f_v_r.`begin`, f_v_r.`end`
FROM shop_seofilter_filter f
LEFT JOIN shop_seofilter_filter_feature_value f_v ON f_v.filter_id = f.id
LEFT JOIN shop_seofilter_filter_feature_value_range f_v_r ON f_v_r.filter_id = f.id
LEFT JOIN shop_feature ff_v ON ff_v.id = f_v.feature_id
LEFT JOIN shop_feature ff_v_r ON ff_v_r.id = f_v_r.feature_id
WHERE f.id IN (' . implode(',', $ids) . ')
';

		$model = new waModel();
		$query = $model->query($sql);

		$filters = array();
		$feature_values = array();
		$feature_value_ranges = array();

		$f_ar = new shopSeofilterFilter();
		$a_length = $f_ar->attributesLength();
		foreach ($query as $row)
		{
			$filter_id = $row['id'];

			if (!isset($filters[$filter_id]))
			{
				$filters[$filter_id] = new shopSeofilterFilter(array_slice($row, 0, $a_length, true));
				$feature_values[$filter_id] = array();
				$feature_value_ranges[$filter_id] = array();
			}

			if ($row['f_v_id'] !== null && !isset($feature_values[$filter_id][$row['f_v_id']]))
			{
				$feature_values[$filter_id][$row['f_v_id']] = new shopSeofilterFilterFeatureValue(array(
					'filter_id' => $filter_id,
					'feature_id' => $row['f_v_feature_id'],
					'value_id' => $row['f_v_value_id'],
					'feature' => array('code' => $row['v_code'], 'id' => $row['f_v_feature_id']),
				));
			}


			if ($row['f_v_r_id'] !== null && !isset($feature_value_ranges[$filter_id][$row['f_v_r_id']]))
			{
				$attributes = array(
					'filter_id' => $filter_id,
					'feature_id' => $row['f_v_r_feature_id'],
					'type' => $row['f_v_r_type'],
					'unit' => $row['f_v_r_unit'],
					'begin' => $row['begin'],
					'end' => $row['end'],
					'feature' => array('code' => $row['v_r_code'], 'id' => $row['f_v_r_feature_id']),
				);
				$feature_value_ranges[$filter_id][$row['f_v_r_id']] = new shopSeofilterFilterFeatureValueRange($attributes);
			}
		}

		foreach ($filters as $filter_id => $filter)
		{
			$filter->featureValues = $feature_values[$filter_id];
			$filter->featureValueRanges = $feature_value_ranges[$filter_id];
		}

		return $filters;
	}

	/**
	 * @return array
	 */
	public function getFilter_categories()
	{
		$ids = array();

		foreach ($this->categories as $category)
		{
			$ids[] = $category->category_id;
		}

		return $ids;
	}

	/**
	 * @param array $categories
	 */
	public function setFilter_categories($categories)
	{
		$objects = array();

		foreach ($categories as $id)
		{
			$objects[] = new shopSeofilterFilterCategory($id);
		}

		$this->categories = $objects;
	}

	/**
	 * @return array
	 */
	public function getFilter_storefronts()
	{
		$storefronts = array();

		foreach ($this->storefronts as $category)
		{
			$storefronts[] = $category->storefront;
		}

		return $storefronts;
	}

	/**
	 * @param array $storefronts
	 */
	public function setFilter_storefronts($storefronts)
	{
		$objects = array();

		foreach ($storefronts as $storefront)
		{
			$objects[] = new shopSeofilterFilterStorefront($storefront);
		}

		$this->storefronts = $objects;
	}

	public function getFilter_category_names()
	{
		$names = array();

		foreach ($this->categories as $category)
		{
			$row = $category->category;

			if ($row)
			{
				$names[] = $row['name'];
			}
		}

		return $names;
	}

	/**
	 * @return shopSeofilterFilterFeatureValueActiveRecord[]
	 */
	public function getAll_feature_values()
	{
		$all = array_merge($this->featureValues, $this->featureValueRanges);
		usort($all, array($this, 'compareFilterFeatureValuesSort'));

		return $all;
	}

	/**
	 * @param shopSeofilterFilterFeatureValueActiveRecord $fv1
	 * @param shopSeofilterFilterFeatureValueActiveRecord $fv2
	 * @return int
	 */
	private function compareFilterFeatureValuesSort($fv1, $fv2)
	{
		if ($fv1->sort == $fv2->sort)
		{
			return 0;
		}

		return $fv1->sort < $fv2->sort ? -1 : 1;
	}

	/**
	 * @param array $params_1
	 * @param array $params_2
	 * @return bool
	 */
	public static function paramsAreEqual($params_1, $params_2)
	{
		if (count($params_1) != count($params_2))
		{
			return false;
		}

		foreach ($params_1 as $code => $values)
		{
			if (!isset($params_2[$code]))
			{
				return false;
			}

			if (!is_array($values))
			{
				if (is_array($params_2[$code]) || $params_2[$code] != $values)
				{
					return false;
				}
			}
			else
			{
				if (!is_array($params_2[$code]) || count($values) != count($params_2[$code]))
				{
					return false;
				}

				//is range
				if (isset($values['price_min']) || isset($values['price_max']) || isset($values['unit']))
				{
					if (count(array_diff_assoc($values, $params_2[$code])) != 0)
					{
						return false;
					}
				}
				else
				{
					if (count(array_diff($values, $params_2[$code])) != 0)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	public function getFields()
	{
		$all_fields = shopSeofilterFilterFieldModel::getAllFields();

		$model = new shopSeofilterFilterFieldValueModel();
		$existing_values = $model->select('field_id,value')
			->where('filter_id = :filter_id', array('filter_id' => $this->id))
			->fetchAll('field_id', true);

		$filter_fields = array();
		foreach ($all_fields as $field_id => $_)
		{
			$filter_fields[$field_id] = array_key_exists($field_id, $existing_values)
				? $existing_values[$field_id]
				: '';
		}

		return $filter_fields;
	}

	function __get($name)
	{
		return array_key_exists($name, $this->_personal_template)
			? $this->_personal_template[$name]
			: parent::__get($name);
	}

	function __set($name, $value)
	{
		array_key_exists($name, $this->_personal_template)
			? $this->_personal_template[$name] = $value
			: parent::__set($name, $value);
	}

	public static function getSortColumns()
	{
		return array(
			'seo_name' => 'название',
			'url' => 'url',
			'is_enabled' => 'виден',
			'update_datetime' => 'дата обновления',
		);
	}

	public function relations()
	{
		return array(
			'featureValues' => array(self::HAS_MANY, 'shopSeofilterFilterFeatureValue', 'filter_id'),
			'featureValueRanges' => array(self::HAS_MANY, 'shopSeofilterFilterFeatureValueRange', 'filter_id'),
			'personalRules' => array(self::HAS_MANY, 'shopSeofilterFilterPersonalRule', 'filter_id'),
			'categories' => array(self::HAS_MANY, 'shopSeofilterFilterCategory', 'filter_id'),
			'storefronts' => array(self::HAS_MANY, 'shopSeofilterFilterStorefront', 'filter_id'),
			'canonicals' => array(self::HAS_MANY, 'shopSeofilterFilterPersonalCanonical', 'filter_id'),
		);
	}

	/**
	 * @param $params
	 * @return null|shopSeofilterFilter
	 */
	public function getByFeatureValues($params)
	{
		$hash = shopSeofilterFilterFeatureValuesHelper::hash($params);
		$filters = $this->getAllByFields(array(
			'feature_value_hash' => $hash,
			'is_enabled' => self::ENABLED,
		));

		$count = count($filters);
		if ($count == 0)
		{
			return null;
		}
		elseif ($count == 1)
		{
			return reset($filters);
		}

		return shopSeofilterFilterFeatureValuesHelper::resolveHashCollision($filters, $params);
	}

	/**
	 * @param shopSeofilterFilter $filter
	 * @return bool
	 */
	public function compareFeatureValuesDeep($filter)
	{
		$params_1 = $this->getFeatureValuesAsFilterParams();
		$params_2 = $filter->getFeatureValuesAsFilterParams();

		return self::paramsAreEqual($params_1, $params_2);
	}

	public function getFeatureValuesAsFilterParamsForCurrency($currency)
	{
		return $this->getFeatureValuesAsFilterParamsPrivate(self::PARAMS_WITH_STOREFRONT_CURRENCY, $currency);
	}

	public function getFeatureValuesAsFilterParams($get_mode = self::PARAMS_ALL_VALUES)
	{
		return $this->getFeatureValuesAsFilterParamsPrivate($get_mode);
	}

	private function getFeatureValuesAsFilterParamsPrivate($get_values = self::PARAMS_ALL_VALUES, $currency = '')
	{
		$params = array();

		foreach ($this->featureValues as $feature_value)
		{
			$feature = $feature_value->feature;

			if ($feature)
			{
				if ($feature_value->value_id == null)
				{
					$params[$feature->code] = 0;
				}
				else
				{
					if (!isset($params[$feature['code']]))
					{
						$params[$feature->code] = array();
					}

					$params[$feature->code][] = $feature_value->value_id;
				}
			}
		}
		unset($feature_value);

		if ($get_values === self::PARAMS_WITHOUT_RANGE_VALUES)
		{
			return $params;
		}

		foreach ($this->featureValueRanges as $feature_value)
		{
			if ($feature_value->isPrice())
			{
				if ($get_values === self::PARAMS_WITHOUT_CURRENCY_VALUES)
				{
					continue;
				}

				if ($get_values === self::PARAMS_WITH_STOREFRONT_CURRENCY)
				{
					if (strtoupper($currency) != $feature_value->unit)
					{
						continue;
					}

					if ($feature_value->begin !== null)
					{
						$params['price_min'] = $feature_value->begin;
					}
					if ($feature_value->end !== null)
					{
						$params['price_max'] = $feature_value->end;
					}
				}
				else
				{
					$params['price_' . $feature_value->unit] = array();

					if ($feature_value->begin !== null)
					{
						$params['price_' . $feature_value->unit]['price_min'] = $feature_value->begin;
					}
					if ($feature_value->end !== null)
					{
						$params['price_' . $feature_value->unit]['price_max'] = $feature_value->end;
					}
				}
			}
			else
			{
				$feature = $feature_value->feature;

				if ($feature)
				{
					$params[$feature->code] = array();

					if ($feature_value->unit)
					{
						$params[$feature->code]['unit'] = $feature_value->unit;
					}

					if ($feature_value->begin !== null)
					{
						$params[$feature->code]['min'] = $feature_value->begin;
					}
					if ($feature_value->end !== null)
					{
						$params[$feature->code]['max'] = $feature_value->end;
					}
				}
			}
		}
		unset($feature_value);

		return $params;
	}

	public function enableById($ids)
	{
		return $this->toggleById($ids, true);
	}

	public function disableById($ids)
	{
		return $this->toggleById($ids, false);
	}

	/**
	 * @param int[]|int $ids
	 * @param bool $toggle
	 * @return bool
	 */
	public function toggleById($ids, $toggle)
	{
		$success = true;

		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		foreach ($ids as $id)
		{
			$filter = $this->getById($id);

			if (!$filter)
			{
				continue;
			}

			$filter->is_enabled = $toggle
				? self::ENABLED
				: self::DISABLED;

			$success = $filter->save() && $success;
		}
		unset($filter);

		return $success;
	}

	public function countProducts($category_id, $currency)
	{
		$hash = 'all';
		if ($category_id !== null)
		{
			$hash = 'category/' . $category_id;
		}

		$collection = shopSeofilterProductsCollectionFactory::getCollection($hash);
		$collection->filters($this->getFeatureValuesAsFilterParamsForCurrency($currency));

		return $collection->count();
	}

	public function validate()
	{
		$valid = parent::validate();

		if (!$this->seo_name)
		{
			$this->_errors['seo_name'] = 'Название не может быть пустым';
			$valid = false;
		}

		if ($this->categories_use_mode != self::USE_MODE_ALL)
		{
			//if (isset($this->_save_relations['categories']) && count($this->_save_relations['categories']) == 0)
			//{
			//	$valid = false;
			//	$this->_errors['categories'] = 'Выберите категории';
			//}
		}
		if ($this->storefronts_use_mode != self::USE_MODE_ALL)
		{
			//if (isset($this->_save_relations['storefronts']) && count($this->_save_relations['storefronts']) == 0)
			//{
			//	$valid = false;
			//	$this->_errors['storefronts'] = 'Выберите витрины';
			//}
		}

		$valid = $this->validateUrl() && $valid;
		$valid = $this->validateFeaturesValues() && $valid;

		return $valid;
	}

	/**
	 * @param $storefront
	 * @param $category_id
	 * @return shopSeofilterFilterPersonalRule
	 */
	public function getActivePersonalRule($storefront, $category_id)
	{
		$key = $storefront . '/' . $category_id;

		if (!array_key_exists($key, $this->_active_personal_rule))
		{
			$params = array(
				'mode_all' => shopSeofilterFilterPersonalRule::USE_MODE_ALL,
				'mode_listed' => shopSeofilterFilterPersonalRule::USE_MODE_LISTED,
				'mode_except' => shopSeofilterFilterPersonalRule::USE_MODE_EXCEPT,
				'enabled' => shopSeofilterFilterPersonalRule::ENABLED,
				'storefront' => $storefront,
				'category_id' => $category_id,
				'filter_id' => $this->id,
			);

			$rule = new shopSeofilterFilterPersonalRule();
			$rule_storefront = new shopSeofilterFilterPersonalRuleStorefront();
			$rule_category = new shopSeofilterFilterPersonalRuleCategory();

			$rule_table = '`' . $rule->tableName() . '`';
			$storefront_table = '`' . $rule_storefront->tableName() . '`';
			$category_table = '`' . $rule_category->tableName() . '`';

			$sql = '
	SELECT r.default_product_sort, r.is_pagination_templates_enabled, r.seo_h1, r.seo_description, r.meta_title, r.meta_description, r.meta_keywords, r.additional_description,
	   r.seo_h1_pagination, r.seo_description_pagination, r.meta_title_pagination, r.meta_description_pagination, r.meta_keywords_pagination, r.additional_description_pagination
	FROM ' . $rule_table . ' r
	LEFT JOIN ' . $storefront_table . ' s ON s.rule_id = r.id
	LEFT JOIN ' . $category_table . ' c ON c.rule_id = r.id
	WHERE 
	(
		r.storefronts_use_mode = :mode_all
		OR (r.storefronts_use_mode = :mode_listed AND s.storefront = :storefront)
		OR (r.storefronts_use_mode = :mode_except AND r.id NOT IN (
			SELECT DISTINCT r.id
			FROM ' . $rule_table . ' r
			JOIN ' . $storefront_table . ' s ON s.rule_id = r.id
			WHERE r.storefronts_use_mode = :mode_except AND s.storefront = :storefront
			AND r.is_enabled = :enabled
		))
	)
	AND
	(
		r.categories_use_mode = :mode_all
		OR (r.categories_use_mode = :mode_listed AND c.category_id = :category_id)
		OR (r.categories_use_mode = :mode_except AND r.id NOT IN (
			SELECT DISTINCT r.id
			FROM ' . $rule_table . ' r
			JOIN ' . $category_table . ' c ON c.rule_id = r.id
			WHERE r.categories_use_mode = :mode_except AND c.category_id = :category_id
			AND r.is_enabled = :enabled
		))
	)
	AND r.is_enabled = :enabled AND r.filter_id = :filter_id
	GROUP BY r.id
	ORDER BY r.storefronts_use_mode DESC, r.categories_use_mode DESC
	';

			$model = new waModel();
			$personal_rules = $model->query($sql, $params)->fetchAll();

			if (!$personal_rules)
			{
				$this->_active_personal_rule[$key] = null;

				return null;
			}

			$meta_templates = array_shift($personal_rules);
			foreach ($meta_templates as $meta => $template)
			{
				if (mb_strlen(trim($template)))
				{
					continue;
				}

				foreach ($personal_rules as $personal_rule)
				{

					if (mb_strlen(trim($personal_rule[$meta])))
					{
						$meta_templates[$meta] = $personal_rule[$meta];
						break;
					}
				}
			}

			$this->_active_personal_rule[$key] = new shopSeofilterFilterPersonalRule($meta_templates);
		}

		return $this->_active_personal_rule[$key];
	}

	public function getDefaultSortSort()
	{
		if (!$this->default_product_sort)
		{
			return '';
		}

		$tmp = explode(' ', $this->default_product_sort);
		return count($tmp) > 0 ? $tmp[0] : '';
	}

	public function getDefaultSortOrder()
	{
		if (!$this->default_product_sort)
		{
			return '';
		}

		$tmp = explode(' ', $this->default_product_sort);
		return count($tmp) == 2 ? $tmp[1] : '';
	}

	protected function beforeSave()
	{
		$this->feature_value_hash = shopSeofilterFilterFeatureValuesHelper::hash(
			$this->getFeatureValuesAsFilterParams(self::PARAMS_WITHOUT_RANGE_VALUES)
		);

		$this->update_datetime = date('Y-m-d H:i:s');

		$this->url = strtolower($this->url);

		$this->feature_values_count = count($this->featureValues);
		$this->feature_value_ranges_count = count($this->featureValueRanges);

		if (is_bool($this->is_enabled))
		{
			$this->is_enabled = $this->is_enabled
				? self::ENABLED
				: self::DISABLED;
		}

		return true;
	}

	protected function afterSave($save_is_succeeded)
	{
		if (!$save_is_succeeded)
		{
			return;
		}

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if ($settings->disable_on_save_handlers)
		{
			return;
		}

		$ids = $this->getAffectedCategories();
		if (!count($ids))
		{
			return;
		}

		$sitemap_cache = new shopSeofilterSitemapCache();

		foreach ($ids as $id)
		{
			$sitemap_cache->invalidateForCategories(array($id));
		}
	}

	public function jsKey($currency)
	{
		$key = shopSeofilterFilterFeatureValuesHelper::key(
			$this->getFeatureValuesAsFilterParamsForCurrency($currency)
		);

		return preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $key);
	}

	public function setPersonalMeta($template)
	{
		foreach ($this->_personal_template as $tag => $val)
		{
			if (isset($template[$tag]) && $template[$tag] !== null && strlen($template[$tag]) > 0)
			{
				$this->_personal_template[$tag] = $template[$tag];
			}
		}
	}

	/**
	 * @param integer $length
	 * @return shopSeofilterFilterFeatureValue[][]
	 */
	public function getFeatureValuesCombinations($length)
	{
		if ($length == 0)
		{
			return array(
				'' => array(),
			);
		}

		$feature_values = $this->featureValues;
		$max_index = count($feature_values) - 1;

		$combinations = array();
		$combination_indexes = range(0, $length - 1);

		do
		{
			list($key, $combination) = $this->formFeatureValuesCombination($combination_indexes);
			$combinations[$key] = $combination;
		}
		while (self::tryMoveIndex($combination_indexes, $max_index));

		unset($combination);

		return $combinations;
	}

	/**
	 * @param int|array $category
	 * @param int $url_type
	 * @param bool $absolute
	 * @param string $domain
	 * @param string $route_url
	 * @return string
	 */
	public function getFrontendCategoryUrl($category, $url_type = null, $absolute = false, $domain = null, $route_url = null)
	{
		if (!is_array($category))
		{
			$category_id = $category;
			$model = new shopCategoryModel();
			$category = $model->getById($category_id);

			if (!$category)
			{
				return '';
			}
		}

		if ($url_type === null)
		{
			$url_type = waRequest::param('url_type');
		}

		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$seofilter_url = new shopSeofilterFilterUrl($settings->url_type, $url_type);

		return $seofilter_url->getFrontendPageUrl($category, $this, $absolute, $domain, $route_url);
	}

	/**
	 * @param int|array $category
	 * @param array $kept_param_names
	 * @return string
	 */
	public function getFrontendCategoryUrlWithAdditionalParams($category, $kept_param_names = array())
	{
		$url = $this->getFrontendCategoryUrl($category);

		$isOriginalRequestUriHasSort = shopSeofilterRouting::instance()->isOriginalRequestUriHasSort();

		$kept_param_names = $isOriginalRequestUriHasSort
			? array('sort', 'order')
			: array();

		$_keep = is_array($kept_param_names)
			? array_fill_keys($kept_param_names, 1)
			: array();

		$params = array();
		$special_params = shopSeofilterFilterFeatureValuesHelper::getCurrentSpecialGetParams();
		foreach ($special_params as $param_name)
		{
			if (
				!array_key_exists($param_name, $_keep)
				&& ($param_name == 'sort' || $param_name == 'order' || $param_name == '_')
			)
			{
				continue;
			}

			$value = waRequest::get($param_name);
			if ($value || ($param_name === 'page' && $value > 1))
			{
				$params[$param_name] = $value;
			}
		}

		$query = count($params)
			? '?' . http_build_query($params, null, '&')
			: '';

		return $url . $query;
	}

	private function formFeatureValuesCombination($indexes)
	{
		$combination = array();

		foreach ($indexes as $index)
		{
			$key = $this->featureValues[$index]->key();
			$combination[$key] = $this->featureValues[$index];
		}

		$keys = array_keys($combination);
		sort($keys);

		return array(implode('|', $keys), $combination);
	}

	private static function tryMoveIndex(&$indexes, $max_index)
	{
		$i = 0;

		if (count($indexes) == 1)
		{
			$indexes[0]++;

			return $indexes[0] <= $max_index;
		}

		for (; $i < count($indexes) - 1; $i++)
		{
			if ($indexes[$i] + 1 < $indexes[$i + 1])
			{
				$indexes[$i]++;
				return true;
			}
		}

		if ($indexes[$i] == $max_index)
		{
			return false;
		}

		$indexes[$i]++;
		return true;
	}

	/**
	 * @return bool
	 */
	private function validateUrl()
	{
		$valid = true;

		if (!$this->url)
		{
			$this->_errors['url'] = 'Url не может быть пустым';
			$valid = false;
		}
		else
		{
			$params = array(
				'url' => $this->url,
			);
			$sql = '
SELECT COUNT(*)
FROM `' . $this->tableName() . '`
WHERE `url` = :url AND `is_enabled` = 1';

			if (!$this->getIsNewRecord() && $this->id)
			{
				$sql .= ' AND `id` <> :id';
				$params['id'] = $this->id;
			}

			$count_by_url = $this->model()->query($sql, $params)->fetchField();
			if ($count_by_url > 0)
			{
				$valid = false;
				$this->_errors['url'] = 'Фильтр с таким url уже есть и включён';
			}
		}

		return $valid;
	}

	/**
	 * @return bool
	 */
	private function validateFeaturesValues()
	{
		if ($this->is_enabled == shopSeofilterFilter::DISABLED)
		{
			return true;
		}

		$is_valid = true;

		$params = $this->getFeatureValuesAsFilterParams(shopSeofilterFilter::PARAMS_ALL_VALUES);

		$existing_filter = $this->getByFeatureValues($params);

		if (
			$existing_filter
			&& ($this->getIsNewRecord() || $this->id != $existing_filter->id)
			&& $existing_filter->compareFeatureValuesDeep($this)
		)
		{
			if (!isset($this->_errors[self::ERROR_KEY_FEATURE_VALUES]))
			{
				$this->_errors[self::ERROR_KEY_FEATURE_VALUES] = array();
			}

			$this->_errors[self::ERROR_KEY_FEATURE_VALUES]['message'] = 'Фильтр для таких характеристик уже включён';
			$this->_errors[self::ERROR_KEY_FEATURE_VALUES]['type'] = 'ERROR_KEY_FEATURE_VALUES';
			$this->_errors[self::ERROR_KEY_FEATURE_VALUES]['double_filter_id'] = $existing_filter->id;

			$is_valid = false;
		}

		return $is_valid;
	}

	public function getAffectedCategories()
	{
		if (self::$_category_ids === null)
		{
			self::$_category_ids = array();

			$model = new shopCategoryModel();
			foreach ($model->select('id')->query() as $row)
			{
				self::$_category_ids[$row['id']] = $row['id'];
			}
		}

		switch ($this->categories_use_mode)
		{
			case self::USE_MODE_ALL:
				return array_keys(self::$_category_ids);
			case self::USE_MODE_LISTED:
				return array_keys(array_intersect_key(self::$_category_ids, array_flip($this->filter_categories)));
			case self::USE_MODE_EXCEPT:
				$ids = self::$_category_ids;
				foreach ($this->filter_categories as $category_id)
				{
					unset($ids[$category_id]);
				}
				return array_keys($ids);
			default:
				return array();
		}
	}

	public function getAffectedStorefronts()
	{
		if (self::$_storefronts === null)
		{
			self::$_storefronts = array();
			foreach (shopSeofilterStorefrontModel::getStorefronts() as $storefront)
			{
				self::$_storefronts[$storefront] = $storefront;
			}
		}

		switch ($this->categories_use_mode)
		{
			case self::USE_MODE_ALL:
				return self::$_storefronts;
			case self::USE_MODE_LISTED:
				return array_keys(array_intersect_key(self::$_storefronts, array_flip($this->filter_storefronts)));
			case self::USE_MODE_EXCEPT:
				$storefronts = self::$_storefronts;
				foreach ($this->filter_storefronts as $storefront)
				{
					unset($storefronts[$storefront]);
				}
				return $storefronts;
			default:
				return array();
		}
	}

	public function isAppliedToStorefrontCategory($storefront, $category_id)
	{
		if ($this->storefronts_use_mode == self::USE_MODE_LISTED && !in_array($storefront, $this->filter_storefronts))
		{
			return false;
		}

		if ($this->storefronts_use_mode == self::USE_MODE_EXCEPT && in_array($storefront, $this->filter_storefronts))
		{
			return false;
		}

		if ($this->categories_use_mode == self::USE_MODE_LISTED && !in_array($category_id, $this->filter_categories))
		{
			return false;
		}

		if ($this->categories_use_mode == self::USE_MODE_EXCEPT && in_array($category_id, $this->filter_categories))
		{
			return false;
		}

		return true;
	}

	public function hasDeletedFeatureValues()
	{
		foreach ($this->featureValues as $feature_value)
		{
			if ($feature_value->feature === null || $feature_value->value_value === null)
			{
				return true;
			}
		}

		$currency_model = new shopCurrencyModel();

		foreach ($this->featureValueRanges as $feature_value_range)
		{
			if ($feature_value_range->isPrice())
			{
				$currency = $feature_value_range->unit;

				if ($currency_model->countByField('code', strtoupper($currency)) == 0)
				{
					return true;
				}
			}
			else
			{
				if ($feature_value_range->feature === null)
				{
					return true;
				}
			}
		}

		return false;
	}
}
