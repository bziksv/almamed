<?php

class shopSearchproPluginFrontendHelperController extends waController
{
	public function execute()
	{
		$plugin = shopSearchproPlugin::getInstance();
		if (!$plugin->getSettings('status')) {
			return;
		}

		$frontend = new shopSearchproFrontend(null, $plugin->getSettings(), shopSearchproPlugin::getEnv());

		$params = array(
			'history' => array(
				'status' => (bool) $plugin->getSettings('dropdown_history_is_visible'),
				'max' => (int) $plugin->getSettings('dropdown_history_max_count'),
			),
			'popular' => array(
				'status' => (bool) $plugin->getSettings('dropdown_popular_is_visible'),
				'max' => (int) $plugin->getSettings('dropdown_popular_max_count'),
			),
		);

		$this->getResponse()->addHeader('Cache-Control', 'private, max-age=300');
		echo $frontend->helperDropdown($params);
	}
}
