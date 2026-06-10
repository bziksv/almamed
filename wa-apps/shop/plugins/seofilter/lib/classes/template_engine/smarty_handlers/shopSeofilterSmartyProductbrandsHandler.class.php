<?php


class shopSeofilterSmartyProductbrandsHandler
{
	public function handle(
		/** @noinspection PhpUnusedParameterInspection */
		$_, Smarty_Internal_Template $smarty)
	{
		$brand = $smarty->getTemplateVars('brand');

		$this->updateCategoryUrls($brand, $smarty);
	}

	private function updateCategoryUrls($brand, Smarty_Internal_Template $smarty)
	{
		$template_categories = $smarty->getTemplateVars('categories');
		if (!is_array($template_categories) || !count($template_categories))
		{
			return;
		}

		$feature_id = wa()->getSetting('feature_id', null, array('shop', 'productbrands'));

		$category_model = new shopCategoryModel();

		$categories = array();
		foreach ($template_categories as $category_id => $category_data)
		{
			$category = $category_model->getById($category_id);

			if ($category)
			{
				$url = shopSeofilterViewHelper::getFilterUrl($feature_id, $brand['id'], null, $category);

				if ($url)
				{
					$categories[$category_id] = $category_data;
					$categories[$category_id]['url'] = $url;
				}
			}
		}

		$smarty->assign('categories', $categories);
	}
}