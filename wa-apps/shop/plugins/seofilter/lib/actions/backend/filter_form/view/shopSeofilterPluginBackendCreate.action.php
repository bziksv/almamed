<?php

class shopSeofilterPluginBackendCreateAction extends shopSeofilterBackendFilterFormViewAction
{
	public function execute()
	{
		$this->left_sidebar['pages']['add']['current'] = true;

		$this->setTemplate('FilterCreate');

		$filter = new shopSeofilterFilter();
		$filter->normalizeAttributes();

		$this->preSetFilterByGetParams($filter);

		$this->prepareForm($filter);

		$this->view->assign('save_action', shopSeofilterPluginFilterFormActions::ACTION_CREATE);
	}

	private function preSetFilterByGetParams(shopSeofilterFilter $filter)
	{
		$category_id = waRequest::get('category_id');
		if (wa_is_int($category_id) && $category_id > 0)
		{
			$filter->categories_use_mode = shopSeofilterFilter::USE_MODE_LISTED;
			$filter->filter_categories = array($category_id);
		}

		$feature_id = waRequest::get('feature_id');
		$value_id = waRequest::get('value_id');
		if (wa_is_int($feature_id) && $feature_id > 0 && wa_is_int($value_id) && $value_id > 0)
		{
			$feature_value = new shopSeofilterFilterFeatureValue();
			$feature_value->feature_id = $feature_id;
			$feature_value->value_id = $value_id;

			$filter->featureValues = array($feature_value);

			$filter->seo_name = $feature_value->getValueName();
			$filter->url = shopSeofilterFilterUrl::generateUniqueUrl($filter);
		}
	}
}