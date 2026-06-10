<?php

class shopSeofilterSeoHelperVersion2 extends shopSeofilterAbstractSeoHelper
{
	/**
	 * @param $storefront
	 * @param $category
	 * @param $page
	 * @param waSmarty3View $view
	 * @return shopSeofilterSeoHelperFrontendCategorySeoData
	 */
	public function getFrontendCategorySeoData($storefront, $category, $page, $view)
	{
		$seo_data = new shopSeofilterSeoHelperFrontendCategorySeoData();

		$collector = new shopSeoCategoryCollector(
			$storefront,
			$category['id'],
			$page
		);

		$case = new shopSeoCategoryCase(
			$storefront,
			$category['id'],
			$page
		);

		if (method_exists($case, 'setIsEnableHandleResponseVariable'))
		{
			$case->setIsEnableHandleResponseVariable(false);
		}

		$data = $collector->getData();
		$h1_template = $data['h1'];
		$description_template = $data['description'];
		$meta_title_template = $data['meta_title'];

		if (mb_strlen($h1_template))
		{
			$seo_data->h1 = $case->fetch($h1_template);
		}

		if (mb_strlen($description_template))
		{
			$seo_data->description = $case->fetch($description_template);
		}

		if (array_key_exists('additional_description', $data) && mb_strlen($data['additional_description']))
		{
			$seo_data->additional_description = $case->fetch($data['additional_description']);
		}

		if (mb_strlen($meta_title_template))
		{
			$seo_data->meta_title = $case->fetch($meta_title_template);
		}

		$this->fillFrontendCategorySeoDataEmptyFields($seo_data, $category);

		return $seo_data;
	}

	public function getCategoryData($storefront, $category, $page, $fields_to_fetch)
	{
		$collector = new shopSeoCategoryCollector(
			$storefront,
			$category['id'],
			$page
		);

		$case = new shopSeoCategoryCase(
			$storefront,
			$category['id'],
			$page
		);
		if (method_exists($case, 'setIsEnableHandleResponseVariable'))
		{
			$case->setIsEnableHandleResponseVariable(false);
		}

		$seo_plugin_templates = $collector->getData();
		$seo_templates_to_fetch = array();

		foreach ($fields_to_fetch as $field)
		{
			if (isset($seo_plugin_templates[$field]))
			{
				$seo_templates_to_fetch[$field] = $seo_plugin_templates[$field];
			}
		}

		$category_data = new shopSeofilterSeoHelperCategoryData();
		foreach ($case->fetchAll($seo_templates_to_fetch) as $field => $fetched_value)
		{
			$category_data->$field = $fetched_value;
		}

		return $category_data;
	}

	public function extendCategory($storefront, $category, $page)
	{
		return shopSeoViewHelper::extendCategory($category, false, $storefront);
	}

	public function getSeoNames($storefront, $category_ids)
	{
		if (!class_exists('shopSeoTemplateCategoryModel')
			|| !is_callable(
				array('shopSeoTemplateCategoryModel', 'getGroup')
			)
		)
		{
			return '';
		}

		$category_seo_names = array();

		$seo_cat = new shopSeoTemplateCategoryModel();

		//$category_ids[] = $category_id;

		$groups = $seo_cat->getGroup($category_ids, $storefront, 'data', array('seo_name'));
		$default_storefront_groups = $seo_cat->getGroup($category_ids, '*', 'data', array('seo_name'));

		foreach ($groups as $group)
		{
			$category_seo_names[$group['category_id']] = $group['value'];
		}

		foreach ($default_storefront_groups as $group)
		{
			if (!isset($category_seo_names[$group['category_id']]) || !strlen(trim($category_seo_names[$group['category_id']])))
			{
				$category_seo_names[$group['category_id']] = $group['value'];
			}
		}
		unset($group);

		return $category_seo_names;
	}

	public function isPluginEnabled()
	{
		return shopSeofilterSeoHelper::isPluginInstalled() && shopSeoSettings::isEnablePlugin();
	}

	public function getCategoryCustomFieldNames()
	{
		$fields_model = new shopSeoFieldCategoryModel();

		return $fields_model->getAll('id', true);
	}
}