<?php

class shopXmlPluginBackendDeleteProfileController extends waJsonController
{
	public function execute()
	{
		shopXmlPlugin::registerAutoload();

		if (!wa()->getUser()->isAdmin() && !wa()->getUser()->getRights('shop', 'settings')) {
			throw new waRightsException('Access denied');
		}

		wa()->getStorage()->close();

		$name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
		if ($name === '' || !preg_match('~^[a-zA-Z0-9_\-]+$~', $name)) {
			$this->setError('Некорректное имя профиля');
			return;
		}

		$settings_model = new waAppSettingsModel();
		$existing = $settings_model->get('shop.xml', $name, null);
		if ($existing === null) {
			$this->response = array('deleted' => false);
			return;
		}

		$settings_model->del('shop.xml', $name);
		$this->response = array('deleted' => true);
	}
}
