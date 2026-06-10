<?php

abstract class shopSeofilterBackendFilterFormJsonController extends shopSeofilterBackendJsonController
{
	private $reply_as_error = false;

	protected $save_feature_value_id_map = array();

	protected $validate_only = false;

	protected function preExecute()
	{
		$user_rights = new shopSeofilterUserRights();
		if (!$user_rights->hasRights())
		{
			throw new waException('Доступ запрещен', 403);
		}
	}

	protected function formError($message)
	{
		$this->errors['action'] = waRequest::post('action');
		$this->errors[] = $message;

		$this->reply_as_error = true;
	}

	protected function postExecute()
	{
		$this->response['action'] = waRequest::post('action');
		$this->response['errors'] = $this->errors;

		if (!$this->reply_as_error)
		{
			$this->errors = array();
		}
	}

	/**
	 * @param array $filter_attributes
	 * @return shopSeofilterFilter
	 */
	protected function prepareFilter($filter_attributes)
	{
		$filter = new shopSeofilterFilter();

		$storefronts = array_key_exists('storefronts', $filter_attributes) && is_array($filter_attributes['storefronts'])
			? $filter_attributes['storefronts']
			: array();
		$categories = array_key_exists('categories', $filter_attributes) && is_array($filter_attributes['categories'])
			? $filter_attributes['categories']
			: array();

		unset($filter_attributes['storefronts']);
		unset($filter_attributes['categories']);

		$filter->setAttributes($filter_attributes);

		$filter->filter_storefronts = $storefronts;
		$filter->filter_categories = $categories;

		if ($filter->storefronts_use_mode === null)
		{
			$filter->storefronts_use_mode = shopSeofilterFilter::USE_MODE_ALL;
		}

		return $filter;
	}

	protected function prepareRelatedObjects(shopSeofilterFilter $filter, $features_values_attributes, $personal_rules_attributes, $personal_canonicals_attributes, $preserve_ids = true)
	{
		if (count($features_values_attributes) == 0)
		{
			if (!isset($this->errors[shopSeofilterFilter::ERROR_KEY_FEATURE_VALUES]))
			{
				$this->errors[shopSeofilterFilter::ERROR_KEY_FEATURE_VALUES] = array();
			}

			$this->errors[shopSeofilterFilter::ERROR_KEY_FEATURE_VALUES]['message'] = 'Добавьте хотя бы одну характеристику';
			$this->validate_only = true;
		}
		else
		{
			list($filter->featureValues, $filter->featureValueRanges)
				= $this->prepareFeatureValues($features_values_attributes, $preserve_ids);
		}

		$filter->personalRules = $this->preparePersonalRules($personal_rules_attributes, $preserve_ids);
		$filter->canonicals = $this->preparePersonalCanonicals($personal_canonicals_attributes, $preserve_ids);
	}

	protected function saveFilter(shopSeofilterFilter $filter)
	{
		if ($this->validate_only)
		{
			if (!$filter->validate())
			{
				$this->errors = array_merge_recursive($this->errors, $filter->errors());
			}
		}
		else
		{
			if ($filter->save())
			{
				$settings = shopSeofilterBasicSettingsModel::getSettings();
				$seofilter_url = new shopSeofilterFilterUrl($settings->url_type, waRequest::param('url_type'));

				$filter_attributes = new shopSeofilterFilterAttributes($filter);
				$filter_attributes->setFullUrl($seofilter_url->getFilterUrlSuffix($filter));

				wa()->event('shop_seofilter_filter_save', $filter_attributes);
			}
			else
			{
				$this->validate_only = true;
				$this->errors = array_merge_recursive($this->errors, $filter->errors());
			}
		}
	}

	protected function saveFilterFieldValues(shopSeofilterFilter $filter, array $field_values)
	{
		$model = new shopSeofilterFilterFieldValueModel();

		$model->setFilterValues($filter->id, $field_values);
	}

	private function prepareFeatureValues($features_values_attributes, $preserve_ids)
	{
		$feature_values = array();
		$feature_value_ranges = array();

		foreach ($features_values_attributes as $attributes)
		{
			$attributes['sort'] = $attributes['_sort'];
			unset($attributes['_sort']);

			$ar_model = $attributes['is_range']
				? new shopSeofilterFilterFeatureValueRange()
				: new shopSeofilterFilterFeatureValue();

			if (array_key_exists('id', $attributes) && $attributes['id'] > 0)
			{
				$obj = $ar_model->getById($attributes['id']);
				$ar_model = $obj ? $obj : $ar_model;
			}

			$ar_model->setAttributes($attributes);
			if (!$preserve_ids)
			{
				$ar_model->id = null;
			}

			if ($attributes['is_range'])
			{
				$feature_value_ranges[] = $ar_model;
			}
			else
			{
				$feature_values[] = $ar_model;
			}
		}

		return array($feature_values, $feature_value_ranges);
	}

	private function preparePersonalRules($personal_rules_attributes, $preserve_ids)
	{
		if (!$this->checkStorefrontsCategoriesUniqueness($personal_rules_attributes))
		{
			$this->validate_only = true;
		}

		$personal_rules = array();
		foreach ($personal_rules_attributes as $rule_attributes)
		{
			$rule = new shopSeofilterFilterPersonalRule();

			if (array_key_exists('id', $rule_attributes) && $rule_attributes['id'] > 0)
			{
				$obj = $rule->getById($rule_attributes['id']);
				$rule = $obj ? $obj : $rule;
				unset($obj);
			}

			$storefronts = array_key_exists('storefronts', $rule_attributes) && is_array($rule_attributes['storefronts'])
				? $rule_attributes['storefronts']
				: array();
			$categories = array_key_exists('categories', $rule_attributes) && is_array($rule_attributes['categories'])
				? $rule_attributes['categories']
				: array();

			unset($rule_attributes['storefronts']);
			unset($rule_attributes['categories']);

			$rule->setAttributes($rule_attributes);
			if (!$preserve_ids)
			{
				$rule->id = null;
			}


			$rule->rule_storefronts = $storefronts;
			$rule->rule_categories = $categories;

			$personal_rules[] = $rule;
		}
		unset($rule);

		return $personal_rules;
	}

	private function preparePersonalCanonicals($canonicals_attributes, $preserve_ids)
	{
		$canonicals = array();

		foreach ($canonicals_attributes as $canonical_attributes)
		{
			$canonical = new shopSeofilterFilterPersonalCanonical();

			if (array_key_exists('id', $canonical_attributes) && $canonical_attributes['id'] > 0)
			{
				$obj = $canonical->getById($canonical_attributes['id']);
				$canonical = $obj ? $obj : $canonical;
				unset($obj);
			}

			$canonical->setAttributes($canonical_attributes);
			if (!$preserve_ids)
			{
				$canonical->id = null;
			}

			$canonical->is_enabled = $canonical->is_enabled
				? shopSeofilterFilterPersonalCanonical::ENABLED
				: shopSeofilterFilterPersonalCanonical::DISABLED;

			$canonicals[] = $canonical;
		}
		unset($canonical);

		return $canonicals;
	}

	private function checkStorefrontsCategoriesUniqueness($personal_rules_attributes)
	{
		$is_unique = true;

		$rule_intersections = array();
		foreach ($personal_rules_attributes as $attributes)
		{
			$rule_id = $attributes['id'];
			$storefronts = array_key_exists('storefronts', $attributes) && is_array($attributes['storefronts'])
				? $attributes['storefronts']
				: array();
			$categories = array_key_exists('categories', $attributes) && is_array($attributes['categories'])
				? $attributes['categories']
				: array();

			sort($storefronts);
			sort($categories);

			$key = implode('/', $storefronts) . '|' . implode('/', $categories);
			if (isset($rule_intersections[$key]))
			{
				$rule_intersections[$key][] = $rule_id;
				$is_unique = false;
			}

			$rule_intersections[$key] = array($rule_id);
		}

		if (!$is_unique)
		{
			return $rule_intersections;
		}

		return $is_unique;
	}
}