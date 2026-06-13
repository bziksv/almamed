<?php

class shopXmlPluginBackendEditAction extends waViewAction
{
	public function execute()
	{
		if (!wa()->getUser()->isAdmin() && !wa()->getUser()->getRights('shop', 'settings')) {
			throw new waRightsException('Access denied');
		}

		$plugin = wa('shop')->getPlugin('xml');
		$this->setLayout(new shopBackendLayout());
		$this->layout->assign('no_level2', true);

		$brands = new shopProductbrandsPlugin(false);
		$cat_parent = $plugin->getFullTree(false, true, array(), true);

		$brand_list = $brands->getBrands();

		$this->view->assign('cat', $cat_parent);
		$this->view->assign('brand', $brand_list);
		$this->view->assign('cat_count', count($cat_parent));
		$this->view->assign('brand_count', count($brand_list));
		$this->view->assign('run_url', '?plugin=xml&action=run');
	}
}
