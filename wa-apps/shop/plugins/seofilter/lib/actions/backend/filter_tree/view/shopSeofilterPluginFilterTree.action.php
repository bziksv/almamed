<?php

class shopSeofilterPluginFilterTreeAction extends shopSeofilterBackendViewAction
{
	public function execute()
	{
		$this->left_sidebar['pages']['all']['current'] = true;

		$this->view->assign('seofilter_tree_settings', array(
			'categories_settings' => $this->getCategoriesSettings(),
		));
		$this->view->assign('categories_tree', $this->getCategoriesTree());
		$this->view->assign('storefronts', $this->getStorefronts());
	}

	private function getCategoriesTree()
	{
		$category_model = new shopCategoryModel();

		return array_values($category_model->getFullTree());
	}

	private function getCategoriesSettings()
	{
		$storage = new shopSeofilterFilterTreeSettingsStorage();

		$categoriesSettings = $storage->getCategoriesSettings();

		return $categoriesSettings;
	}

	private function getStorefronts()
	{
		$storefront_model = new shopSeofilterStorefrontModel();

		return $storefront_model->getStorefronts();
	}
}