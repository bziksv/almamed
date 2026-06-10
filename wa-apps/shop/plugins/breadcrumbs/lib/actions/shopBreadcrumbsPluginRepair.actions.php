<?php

class shopBreadcrumbsPluginRepairActions extends waJsonActions
{
	protected $response = 'Ok';

	public function defaultAction()
	{
		$this->response = "Available repair actions:\r\n\tclean\r\n\tthemeSettingsToUtf8Mb4\r\n\tthemeSettingsToUtf8";
	}

	public function cleanAction()
	{
		$cleaner = new shopBreadcrumbsCleaner();
		$cleaner->clean();
	}

	public function themeSettingsToUtf8Mb4Action()
	{
		$theme_settings_model = new shopBreadcrumbsThemeSettingsModel();
		$migration = new shopBreadcrumbsTableMigration();

		$migration->toUtf8Mb4($theme_settings_model);

		$settings_model = new waAppSettingsModel();
		$settings_model->set('shop.breadcrumbs', shopBreadcrumbsThemeSettingsModel::USE_UTF8MB4_SETTING_NAME, 1);
	}

	public function themeSettingsToUtf8Action()
	{
		$theme_settings_model = new shopBreadcrumbsThemeSettingsModel();
		$migration = new shopBreadcrumbsTableMigration();

		$migration->toUtf8($theme_settings_model);

		$settings_model = new waAppSettingsModel();
		$settings_model->set('shop.breadcrumbs', shopBreadcrumbsThemeSettingsModel::USE_UTF8MB4_SETTING_NAME, 0);
	}

	public function run($params = null)
	{
		$action = $params;
		if (!$action)
		{
			$action = 'default';
		}
		$this->action = $action;

		$this->preExecute();
		$this->execute($this->action);
		$this->postExecute();

		if ($this->action == $action)
		{
			if (waRequest::isXMLHttpRequest())
			{
				$this->getResponse()->addHeader('Content-type', 'application/json');
			}
			$this->getResponse()->sendHeaders();
			if (!$this->errors)
			{
				echo '<pre>' . $this->response . '</pre>';
			}
			else
			{
				echo '<pre>' . json_encode(array('status' => 'fail', 'errors' => $this->errors)) . '</pre>';
			}
		}
	}
}