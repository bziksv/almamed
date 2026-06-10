<?php

class shopSeofilterCatalogreviewsCategoryContext extends shopSeofilterContext
{
	private $storefront;
	private $category_id;
	private $page;
	private $category;
	private $catalogreviews_plugin_helper;

	public function __construct(shopSeofilterFrontendFilter $frontend_filter, $currency, $storefront, $category_id, $page)
	{
		parent::__construct($frontend_filter, $currency);

		$this->storefront = $storefront;
		$this->category_id = $category_id;
		$this->page = $page;

		$category_model = new shopCategoryModel();
		$this->category = $category_model->getById($this->category_id);
		$this->catalogreviews_plugin_helper = new shopSeofilterCatalogreviewsPluginHelper();
	}

	public function getCurrentPageUrl()
	{
		$filter_url = $this->frontend_filter->filter->getFrontendCategoryUrl($this->category);

		return $this->catalogreviews_plugin_helper->transformCategorySeofilterUrlToCategoryReviewsUrl($filter_url);
	}

	public function assign(shopSeofilterParsedTemplate $template)
	{
	}

	protected function updateBreadcrumbs()
	{
	}

	// todo вынести setVars (просто вернуть $view_vars)
	public function prepareContext()
	{
		$category = $this->category;
		$original_name = $category['name'];

		/** @var array $view_vars */
		$view_vars = wa()->getView()->getVars();

		if (isset($view_vars['category']))
		{
			$category = array_merge($view_vars['category'], $category);
		}

		$category = $this->extendCategory($category);
		$category['name'] = $original_name;


		list($parent_category, $root_category, $parent_categories_names) = $this->prepareCategories();
		list($feature_name, $value_name, $filter_features) = $this->prepareFeatures();

		$filter = array(
			'features' => $filter_features,
			'field' => $this->getFilterFields(),
		);

		$feature_names = array();
		$value_names = array();
		$i = 1;
		foreach ($this->frontend_filter->filter->all_feature_values as $feature_value)
		{
			$feature_names[$i] = $feature_value->feature_name;
			$value_names[$i] = $feature_value->getValueName();

			$i++;
		}
		unset($i);

		$view_vars['category'] = $category;
		$view_vars['seo_name'] = $this->frontend_filter->seo_name;
		$view_vars['feature_name'] = $feature_name;
		$view_vars['feature_names'] = $feature_names;
		$view_vars['value_name'] = $value_name;
		$view_vars['value_names'] = $value_names;
		$view_vars['root_category'] = $root_category;
		$view_vars['parent_category'] = $parent_category;
		$view_vars['parent_categories_names'] = $parent_categories_names;
		$view_vars['seo_name'] = $this->frontend_filter->seo_name;
		$view_vars['filter'] = $filter;

		$this->setVars($view_vars);

		$storefront_fields = $this->prepareStorefrontFields();
		foreach ($storefront_fields as $id => $field)
		{
			$storefront_fields[$id]['value'] = $this->fetch($storefront_fields[$id]['value']);
		}
		$this->setVars(array(
			'storefront_field' => $storefront_fields,
		));

		$hook_vars = wa()->event(array('shop', 'seofilter_fetch_templates'));

		foreach ($hook_vars as $plugin_id => $_hook_vars)
		{
			$this->setVars($_hook_vars);
		}
	}

	private function prepareFeatures()
	{
		$params = $this->frontend_filter->params;
		$feature_codes = array_keys($params);

		$features = shopSeofilterFilterFeatureValuesHelper::getFeatures('code', $feature_codes, 'code');

		$filter_features = array();

		$feature_names = array();
		$value_names = array();


		foreach ($feature_codes as $code)
		{
			if (!isset($features[$code]))
			{
				continue;
			}

			$feature_names[] = $features[$code]->name;
		}
		unset($code);

		/** @var shopSeofilterFilterFeatureValueActiveRecord[] $filter_feature_values */
		$filter_feature_values = array_merge(
			$this->frontend_filter->filter->featureValues,
			$this->frontend_filter->filter->featureValueRanges
		);
		foreach ($filter_feature_values as $feature_value)
		{
			$value_names[] = $feature_value->getValueName();
		}
		unset($feature_value);


		list($feature_name, $value_name) = array(implode(' ', $feature_names), implode(' ', $value_names));

		return array($feature_name, $value_name, $filter_features);
	}

	private function getFilterFields()
	{
		$fields = array();

		foreach ($this->frontend_filter->filter->fields as $field_id => $value)
		{
			$fields[$field_id] = array('value' => $value);
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	private function prepareCategories()
	{
		$seo_helper = new shopSeofilterSeoHelper();

		$category_id = $this->category_id;
		$category_model = new shopCategoryModel();

		$path = $category_model->getPath($category_id);
		$parent_category = $category_model->getByField('id', $this->category['parent_id']);
		$root_category = null;
		$parent_category_name = null;
		$parent_categories_names = array();

		if ($parent_category)
		{
			if ($seo_helper->isPluginEnabled())
			{
				$parent_category = $seo_helper->extendCategory($this->storefront, $parent_category, 1);
			}
			else
			{
				$parent_category['seo_name'] = $parent_category['name'];
			}
		}

		$category_ids = array_keys($path);
		$category_ids[] = $category_id;
		$category_seo_names = $seo_helper->getSeoNames($this->storefront, $category_ids);

		foreach ($path as $id => $path_category)
		{
			$category_name = array_key_exists($path_category['id'], $category_seo_names) && $category_seo_names[$path_category['id']]
				? $category_seo_names[$path_category['id']]
				: $path_category['name'];

			$parent_categories_names[] = $category_name;

			if ($parent_category_name === null)
			{
				$parent_category_name = $category_name;
			}
		}

		if (isset($path_category))
		{
			$root_category = $path_category;
			$root_category['seo_name'] = isset($category_seo_names[$root_category['id']]) && $category_seo_names[$root_category['id']]
				? $category_seo_names[$root_category['id']]
				: $root_category['name'];
		}
		$parent_categories_names = array_reverse($parent_categories_names);

		return array(
			$parent_category,
			$root_category,
			$parent_categories_names,
		);
	}

	/**
	 * @return array
	 */
	private function prepareStorefrontFields()
	{
		$fields = shopSeofilterStorefrontFieldsModel::getAllFields();

		$storefront_field = array();
		foreach ($fields as $id => $name)
		{
			$field_key = 'storefront_field_' . $id;

			$field_value = $this->frontend_filter->$field_key;
			$storefront_field[$id] = array(
				'name' => $name,
				'value' => isset($field_value)
					? $field_value
					: '',
			);
		}

		return $storefront_field;
	}

	private function extendCategory($category)
	{
		$seo_helper = new shopSeofilterSeoHelper();

		return $seo_helper->extendCategory($this->storefront, $category, 1);
	}
}