<?php

abstract class shopBreadcrumbsTemplateStorage
{
	const THEME_TEMPLATE_NAME = 'breadcrumbs_plugin.html';
	const THEME_CSS_NAME = 'breadcrumbs_plugin.css';

	abstract protected function getApp();

	private $theme;

	private $settings;

	private $theme_settings_model;

	/**
	 * @param string|waTheme $theme
	 */
	public function __construct($theme = null)
	{
		$this->theme = $this->getTheme($theme);
		$this->settings = $this->getDefaultSettings();
		$this->theme_settings_model = new shopBreadcrumbsThemeSettingsModel();

		if ($this->theme)
		{
			$theme_settings = $this->theme_settings_model
				->select('name,value')
				->where('theme_id = :theme_id', array('theme_id' => $this->theme->id))
				->where('app = :app', array('app' => $this->getApp()))
				->fetchAll('name', true);

			foreach ($this->settings as $setting => $value)
			{
				if (array_key_exists($setting, $theme_settings))
				{
					$this->settings[$setting] = $theme_settings[$setting];
				}
			}
		}
	}

	public function getTemplatePath()
	{
		$plugin_template = $this->getPluginTemplatePath();
		if (!$this->theme)
		{
			return $plugin_template;
		}

		$theme_template = $this->getThemeTemplatePath();

		return file_exists($theme_template)
			? $theme_template
			: $plugin_template;
	}

	public function getCssPath()
	{
		$plugin_css = $this->getPluginCssPath();
		if (!$this->theme)
		{
			return $plugin_css;
		}

		$theme_css = $this->getThemeCssPath();

		return file_exists($theme_css)
			? $theme_css
			: $plugin_css;
	}

	public function getCssUrl()
	{
		$plugin_url = shopBreadcrumbsPlugin::getStaticUrl('css/breadcrumbs.css');
		if (!$this->theme)
		{
			return $plugin_url;
		}

		$theme_url = $this->theme->getUrl() . self::THEME_CSS_NAME;

		return file_exists($this->getThemeCssPath())
			? $theme_url
			: $plugin_url;
	}

	public function getTemplateSettings()
	{
		return array_merge(
			array(
				'template' => file_get_contents($this->getTemplatePath()),
				'css' => file_get_contents($this->getCssPath()),
			),
			$this->settings
		);
	}

	public function hasCustomTemplates()
	{
		if (!$this->theme)
		{
			return false;
		}

		$theme_css = $this->getThemeCssPath();
		$theme_template = $this->getThemeTemplatePath();

		return file_exists($theme_template) || file_exists($theme_css);
	}

	public function getPluginTemplatePath()
	{
		return shopBreadcrumbsPlugin::getPath('templates/view_helper/Breadcrumbs.html');
	}

	public function getPluginCssPath()
	{
		return shopBreadcrumbsPlugin::getPath('css/breadcrumbs.css');
	}

	public function storeCss($css)
	{
		if ($this->cssIsDefault($css))
		{
			$this->theme->removeFile(self::THEME_CSS_NAME);
			waFiles::delete($this->getThemeCssPath());
		}
		else
		{
			$file_info = $this->theme->getFile(self::THEME_CSS_NAME);

			if (count($file_info))
			{
				$this->theme->changeFile(self::THEME_CSS_NAME, 'Плагин "Навигация в хлебных крошках"');
			}
			else
			{
				$this->theme->addFile(self::THEME_CSS_NAME, 'Плагин "Навигация в хлебных крошках"');
			}

			waFiles::write($this->getThemeCssPath(), $css);
		}
	}

	public function storeTemplate($template)
	{
		if ($this->templateIsDefault($template))
		{
			$this->theme->removeFile(self::THEME_TEMPLATE_NAME);
			waFiles::delete($this->getThemeTemplatePath());
		}
		else
		{
			$file_info = $this->theme->getFile(self::THEME_TEMPLATE_NAME);

			if (count($file_info))
			{
				$this->theme->changeFile(self::THEME_TEMPLATE_NAME, 'Плагин "Навигация в хлебных крошках"');
			}
			else
			{
				$this->theme->addFile(self::THEME_TEMPLATE_NAME, 'Плагин "Навигация в хлебных крошках"');
			}

			waFiles::write($this->getThemeTemplatePath(), $template);
		}
	}

	public function storeSettings($settings)
	{
		$this->storeCss($settings['css']);
		$this->storeTemplate($settings['template']);

		unset($settings['css']);
		unset($settings['template']);

		foreach ($this->getDefaultSettings() as $setting => $_)
		{
			if (array_key_exists($setting, $settings))
			{
				$value = $settings[$setting];

				$this->settings[$setting] = $value;
				$this->theme_settings_model->set($this->getApp(), $this->theme->id, $setting, $value);
			}
		}
	}

	public function getDefaultSettings()
	{
		return array(
			'home_name' => 'Главная',
			'separator' => '→',
		);
	}

	public function getSeparator()
	{
		return $this->settings['separator'];
	}

	public function getHomeName()
	{
		return $this->settings['home_name'];
	}

	public function getCssAssetVersion()
	{
		return filemtime($this->getCssPath());
	}

	private function getThemeCssPath()
	{
		return $this->theme->getPath() . '/' . self::THEME_CSS_NAME;
	}

	/**
	 * @param string|waTheme $theme_id
	 * @return waTheme|null
	 */
	private function getTheme($theme_id)
	{
		$helper = new shopBreadcrumbsHelper();

		return $helper->getTheme($this->getApp(), $theme_id);
	}

	private function getThemeTemplatePath()
	{
		$custom_template = $this->theme->getPath() . '/' . self::THEME_TEMPLATE_NAME;

		return $custom_template;
	}

	private function cssIsDefault($css)
	{
		$default_css = file_get_contents($this->getPluginCssPath());

		$css = trim(preg_replace('/\r\n/', '', $css));
		$default_css = trim(preg_replace('/\r\n/', '', $default_css));

		return $css == $default_css;
	}

	private function templateIsDefault($template)
	{
		$default_template = file_get_contents($this->getPluginTemplatePath());

		$template = trim(preg_replace('/\r\n/', '', $template));
		$default_template = trim(preg_replace('/\r\n/', '', $default_template));

		return $template == $default_template;
	}
}