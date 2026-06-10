<?php

class shopSearchproPluginFrontendConfigAction extends waViewAction
{
	public function execute()
	{
		$this->setTemplate(wa()->getAppPath('plugins/searchpro/templates/actions/frontend/FrontendConfig.js', 'shop'));

		$this->getResponse()->addHeader('Content-type', 'text/javascript');
		$this->getResponse()->addHeader('X-Content-Type-Options', 'nosniff');

		$plugin = shopSearchproPlugin::getInstance('config');
		$plugin_url = $plugin->getPluginStaticUrl(true);
		$version = $plugin->getVersion();

		$this->view->assign('plugin_url', $plugin_url);
		$this->view->assign('version', $version);
		$this->view->assign('dropdown_url', wa()->getRouteUrl('shop/frontend/dropdown', array('plugin' => 'searchpro'), true));
		$this->view->assign('results_url', wa()->getRouteUrl('shop/frontend/page', array('plugin' => 'searchpro'), true));
	}
}
