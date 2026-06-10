<?php

abstract class shopSeofilterAbstractSeoHelper
{
	/**
	 * @param $storefront
	 * @param $category
	 * @param $page
	 * @param waSmarty3View $view
	 * @return shopSeofilterSeoHelperFrontendCategorySeoData
	 */
	abstract public function getFrontendCategorySeoData($storefront, $category, $page, $view);

	/**
	 * @param $storefront
	 * @param $category
	 * @param $page
	 * @param array $fields_to_fetch
	 * @return shopSeofilterSeoHelperCategoryData
	 */
	abstract public function getCategoryData($storefront, $category, $page, $fields_to_fetch);

	abstract public function extendCategory($storefront, $category, $page);

	/**
	 * @param $storefront
	 * @param int[] $category_ids
	 * @return array
	 */
	abstract public function getSeoNames($storefront, $category_ids);

	abstract public function isPluginEnabled();

	abstract public function getCategoryCustomFieldNames();

	protected function fillFrontendCategorySeoDataEmptyFields(shopSeofilterSeoHelperFrontendCategorySeoData $seo_data, $category)
	{
		if (trim($seo_data->h1) == '')
		{
			$seo_data->h1 = $category['name'];
		}

		if (trim($seo_data->description) == '')
		{
			$seo_data->description = $category['description'];
		}

		if (trim($seo_data->meta_title) == '')
		{
			$current_title = wa()->getResponse()->getTitle();

			$seo_data->meta_title = trim($current_title) == ''
				? shopCategoryModel::getDefaultMetaTitle($category)
				: $current_title;
		}
	}
}