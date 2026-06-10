<?php

class shopSeofilterSeoHelperVersion3 extends shopSeofilterAbstractSeoHelper
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
		$view_category = $view->getVars('category');
		$view->clearAssign('category');

		$category_extender = shopSeoContext::getInstance()->getCategoryExtender();
		$category_extended = $category_extender->extend(
			$storefront,
			$category,
			$page
		);

		$view->assign('category', $view_category);

		$seo_data = new shopSeofilterSeoHelperFrontendCategorySeoData();
		
		if ($category_extended['seo_name']) {
			$seo_data->h1 = $category_extended['seo_name'];
		} elseif ($category_extended['name']) {
			$seo_data->h1 = $category_extended['name'];
		} else {
			$seo_data->h1 = $category['name'];
		}
		
		$seo_data->description = $category_extended['description'] == '' ? $category['description'] : $category_extended['description'];

		$seo_data->additional_description = '';
		if (array_key_exists('additional_description', $category_extended) && $category_extended['additional_description'] != '')
		{
			$seo_data->additional_description = $category_extended['additional_description'];
		}

		$seo_data->meta_title = $category_extended['meta_title'];

		return $seo_data;
	}

	public function getCategoryData($storefront, $category, $page, $fields_to_fetch)
	{
		$view = wa()->getView();

		$view_category = $view->getVars('category');
		$view->clearAssign('category');

		$category_extender = shopSeoContext::getInstance()->getCategoryExtender();
		$category_extended = $category_extender->extend(
			$storefront,
			$category,
			$page
		);

		$view->assign('category', $view_category);

		$category_data = new shopSeofilterSeoHelperCategoryData();
		foreach ($fields_to_fetch as $field)
		{
			if ($field == 'h1')
			{
				$category_data->h1 = $category_extended['name'];
			}
			else
			{
				$category_data->$field = $category_extended[$field];
			}
		}

		return $category_data;
	}

	public function extendCategory($storefront, $category, $page)
	{
		$category_extender = shopSeoContext::getInstance()->getCategoryExtender();
		$category_extended = $category_extender->extend(
			$storefront,
			$category,
			$page
		);

		return $category_extended;
	}

	public function getSeoNames($storefront, $category_ids)
	{
		$category_seo_names = array();
		foreach ($category_ids as $category_id)
		{
			$category_seo_names[$category_id] = shopSeoContext::getInstance()
				->getCategoryDataCollector()
				->collectSeoName($storefront, $category_id, $_);
		}

		return $category_seo_names;
	}

	public function isPluginEnabled()
	{
		return shopSeoPlugin::isEnabled();
	}

	public function getCategoryCustomFieldNames()
	{
		$field_names = array();

		$fields = shopSeoContext::getInstance()->getCategoryFieldService()->getFields();
		foreach ($fields as $field)
		{
			$field_names[$field->getId()] = $field->getName();
		}

		return $field_names;
	}
}