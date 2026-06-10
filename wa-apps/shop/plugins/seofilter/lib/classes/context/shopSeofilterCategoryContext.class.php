<?php

class shopSeofilterCategoryContext extends shopSeofilterContext
{
	private $storefront;
	private $category_id;
	private $page;
	private $category;

	public function __construct(shopSeofilterFrontendFilter $frontend_filter, $currency, $storefront, $category_id, $page)
	{
		parent::__construct($frontend_filter, $currency);

		$this->storefront = $storefront;
		$this->category_id = $category_id;
		$this->page = $page;

		$category_model = new shopCategoryModel();
		$this->category = $category_model->getById($this->category_id);

		$replace_set = new shopSeofilterReplaceSet($this->getViewBuffer());
		$this->setOldReplacer($replace_set);
	}

	public function getCurrentPageUrl()
	{
		return $this->frontend_filter->filter->getFrontendCategoryUrl($this->category);
	}

	public function assign(shopSeofilterParsedTemplate $template)
	{
		$this->assignCategory($template);

		$seofilter_current_url = $this
			->frontend_filter
			->filter
			//->getFrontendCategoryUrlWithAdditionalParams($this->category, array('sort', 'order'));
			->getFrontendCategoryUrlWithAdditionalParams($this->category);

		wa()->getView()->clearAssign('canonical');
		wa()->getView()->assign('is_seofilter', true);
		wa()->getView()->assign('seofilter_current_url', $seofilter_current_url);
	}

	protected function updateBreadcrumbs()
	{
		/** @var array $category */
		$category = wa()->getView()->getVars('category');
		$breadcrumbs = wa()->getView()->getVars('breadcrumbs');

		$category_url = waRequest::param('url_type') == 1
			? $category['url']
			: $category['full_url'];

		$current_category_url = wa()->getRouteUrl(
			'shop/frontend/category',
			array(
				'category_url' => $category_url,
			)
		);

		$all_vars = $this->getVars();
		$context_category = isset($all_vars['category'])
			? $all_vars['category']
			: $category;

		if (!is_array($breadcrumbs))
		{
			$breadcrumbs = array();
		}
		$breadcrumbs[] = array(
			'url' => $current_category_url,
			'name' => $context_category['name'],
		);

		wa()->getView()->assign('breadcrumbs', $breadcrumbs);
		
		$breadcrumbs_last_chain_name = $this->frontend_filter->filter->seo_name;
		
		$shopSeofilterFilterPersonalRule = $this->frontend_filter->filter->getActivePersonalRule($this->frontend_filter->filter->storefronts_use_mode, $context_category['id']);
		
		if ($shopSeofilterFilterPersonalRule) {
			$breadcrumbs_last_chain_name = $shopSeofilterFilterPersonalRule->seo_h1;
		}

		$event_params = array(
			'breadcrumbs' => array(
				array(
					'url' => $this->getCurrentPageUrl(),
					'name' => $breadcrumbs_last_chain_name,
				),
			)
		);

		wa()->event('breadcrumbs_frontend_breadcrumbs.category', $event_params);
	}

	// todo вынести setVars (просто вернуть $view_vars)
	public function prepareContext()
	{
		$original_name = $this->category['name'];

		/** @var array $view_vars */
		$view_vars = wa()->getView()->getVars();

		if (isset($view_vars['category']))
		{
			$this->category = array_merge($view_vars['category'], $this->category);
		}

		$this->category = $this->extendCategory($this->category);
		$this->category['name'] = $original_name;

		list($parent_category, $root_category, $parent_categories_names, $category_seo_name) = $this->prepareCategories();
		if ($category_seo_name === null || strlen(trim($category_seo_name)) == 0)
		{
			$category_seo_name = $this->category['name'];
		}

		if (!isset($this->category['seo_name']))
		{
			$this->category['seo_name'] = $category_seo_name;
		}

		list($store_name, $store_phone) = $this->prepareStoreInfo();

		$host = wa()->getRouting()->getDomain();

		$store_info = array(
			'name' => $store_name,
			'phone' => $store_phone,
		);

		$config = wa('shop')->getConfig();

		$collection = $this->getProductCollection();
		list($feature_name, $value_name, $filter_features) = $this->prepareFeatures();
		$filter_fields = $this->getFilterFields();

		$store_name = $config instanceof shopConfig
			? $config->getGeneralSettings('name')
			: '';
		$storefront_name = $this->frontend_filter->storefront_name
			? $this->frontend_filter->storefront_name
			: $store_name;

		$context_cached = $this->getCachedContext($collection);

		$price_range = $context_cached['price_range'];
		$products_count = $context_cached['products_count'];

		$limit = $config instanceof shopConfig
			? $config->getOption('products_per_page')
			: $products_count;

		$pages_count = ceil($products_count / $limit);

		$filter = array(
			'min_price' => shop_currency($price_range['min']),
			'min_price_without_currency' => $price_range['min'],
			'max_price' => shop_currency($price_range['max']),
			'max_price_without_currency' => $price_range['max'],
			'products_count' => $products_count,
			'features' => $filter_features,
			'field' => $filter_fields,
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

		$view_vars['feature_names'] = $feature_names;
		$view_vars['value_names'] = $value_names;
		$view_vars['category'] = $this->category;
		$view_vars['root_category'] = $root_category;
		$view_vars['parent_category'] = $parent_category;
		$view_vars['parent_categories_names'] = $parent_categories_names;
		$view_vars['store_info'] = $store_info;
		$view_vars['page_number'] = $this->page;
		$view_vars['host'] = $host;
		$view_vars['seo_name'] = $this->frontend_filter->seo_name;
		$view_vars['feature_name'] = $feature_name;
		$view_vars['value_name'] = $value_name;
		$view_vars['filter'] = $filter;
		$view_vars['pages_count'] = $pages_count;
		$view_vars['storefront'] = array('name' => $storefront_name);

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

	public function fetchFromBufferAll($template)
	{
		return $this->getViewBuffer()->fetch($template);
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

		$category_seo_name = isset($category_seo_names[$category_id])
			? $category_seo_names[$category_id]
			: null;

		return array(
			$parent_category,
			$root_category,
			$parent_categories_names,
			$category_seo_name
		);
	}


	/**
	 * @return shopProductsCollection
	 */
	private function getProductCollection()
	{
		$params = $this->frontend_filter->params;
		$hash = $this->category_id
			? 'category/' . $this->category_id
			: 'all';

		$collection = shopSeofilterProductsCollectionFactory::getCollection($hash);
		$collection->filters($params);

		return $collection;
	}

	private function prepareStoreInfo()
	{
		$config = wa('shop')->getConfig();
		if ($config instanceof shopConfig)
		{
			$store_name = $config->getGeneralSettings('name');
			$store_phone = $config->getGeneralSettings('phone');
		}
		else
		{
			$store_name = '';
			$store_phone = '';
		}

		return array($store_name, $store_phone);
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

	/**
	 * @param shopSeofilterParsedTemplate $template
	 */
	private function assignCategory(shopSeofilterParsedTemplate $template)
	{
		/** @var array $category */
		$category = wa()->getView()->getVars('category');
		if (!$category)
		{
			return;
		}

		$category['original_name'] = $category['name'];

		if ($template->h1)
		{
			$category['name'] = $template->h1;
		}
		$category['description'] = $template->description;


		$settings = shopSeofilterBasicSettingsModel::getSettings();
		if ($settings->category_additional_description_is_enabled)
		{
			$templates = $this->frontend_filter->getTemplates();
			$additional_description = isset($templates['additional_description'])
				? $templates['additional_description']
				: '';
			if (strlen(trim($additional_description)) > 0)
			{
				$category['additional_description'] = $this->fetch($additional_description);
				$this->setVars(array(
					'category' => $category,
				));
			}
		}

		wa()->getView()->assign('category', $category);
	}

	private function extendCategory($category)
	{
		$seo_helper = new shopSeofilterSeoHelper();

		return $seo_helper->extendCategory($this->storefront, $category, 1);
	}

	/**
	 * @param shopProductsCollection $collection
	 * @return array|null
	 *
	 * price_range, products_count
	 */
	private function getCachedContext(shopProductsCollection $collection)
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$cache = null;

		if ($settings->cache_ttl_minutes > 0)
		{
			$ttl_seconds = $settings->cache_ttl_minutes * 60;
			$ttl_seconds *= 1 + rand(-25, 25) * 0.01;

			$cache = new shopSeofilterCategoryContextCache(
				$this->storefront,
				$this->category_id,
				$this->currency,
				$this->frontend_filter->filter->id,
				$ttl_seconds
			);

			$data = $cache->get();
			if (is_array($data))
			{
				return $data;
			}
		}

		$data = array(
			'products_count' => $collection->count(),
			'price_range' => $collection->getPriceRange(),
		);

		if ($cache instanceof shopSeofilterCategoryContextCache)
		{
			$cache->set($data);
		}

		return $data;
	}
}