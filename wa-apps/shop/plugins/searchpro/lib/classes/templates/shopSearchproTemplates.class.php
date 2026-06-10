<?php

class shopSearchproTemplates
{
	protected static $env;

	private $templates;
	private $source_path;
	private $source_css_path;
	private $source_css_url;

	private $custom_path;
	private $custom_url;

	public $theme_id;
	public $theme;
	public $themes;

	public static $template_names = array(
		'field' => 'Поле поиска',
		'dropdown' => 'Выпадающий список результатов',
		'helper_dropdown' => 'Выпадающий список с историей и популярными запросами',
		'filters' => 'Фильтры результатов поиска',
		'page' => 'Страница с результатами поиска',
		'empty_page' => 'Страница "По вашему запросу ничего не найдено"',
		'stylesheet_field' => 'Таблица стилей поля поиска',
		'stylesheet_page' => 'Таблица стилей страницы с результатами поиска',
	);

	public function __construct()
	{
		$this->source_path = wa()->getAppPath('plugins/searchpro/templates/source', 'shop');
		$this->source_css_path = wa()->getAppPath('plugins/searchpro/css', 'shop');
		$this->source_css_url = wa()->getAppStaticUrl('shop', true) . 'plugins/searchpro/css';

		$this->custom_path = wa()->getDataPath('themes', true, 'shop');
		$this->custom_url = wa()->getDataUrl('themes', true, 'shop', true);
	}

	public static function getNames()
	{
		return self::$template_names;
	}

	public static function getName($template_name)
	{
		return ifset(self::$template_names, $template_name, null);
	}

	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopSearchproEnv();

		return self::$env;
	}

	public function register($view)
	{
		$templates = $this->getTemplates(true);
		$theme_id = $this->getThemeId();

		$resource = new shopSearchproSmartyResource($templates, $theme_id);

		return $view->smarty->registerResource('shop_searchpro', $resource);
	}

	public function getThemeId()
	{
		if(!isset($this->theme_id))
			$this->theme_id = $this->getEnv()->getCurrentTheme();

		return $this->theme_id;
	}

	/**
	 * @param $theme_id
	 * @return waTheme
	 */
	public function getTheme($theme_id = null)
	{
		if($theme_id !== null) {
			return $this->getEnv()->getTheme($theme_id);
		}

		return $this->getEnv()->getTheme($this->getThemeId());
	}

	public static function getThemeTemplatePath($name)
	{
		$file = self::getTemplateFile($name);

		return "searchpro_plugin_$file";
	}

	protected function isThemeTemplateExists(waTheme $theme, $path, $is_check_for_file = false)
	{
		$is_theme_template_exists = count($theme->getFile($path));

		if($is_check_for_file) {
			$full_path = $this->getThemeTemplateFullPath($theme, $path);

			$is_theme_template_exists = file_exists($full_path);
		}

		return $is_theme_template_exists;
	}

	protected function deleteThemeTemplate(waTheme $theme, $path)
	{
		$theme->removeFile($path);
	}

	protected function getThemeTemplateFullPath(waTheme $theme, $path)
	{
		$full_path = $theme->getPath() . '/' . $path;

		return $full_path;
	}

	protected function getThemeTemplateDescription($path)
	{
		$path_info = pathinfo($path);

		if($path_info['extension'] === 'css') {
			$description = 'Таблица стилей';
			$template_name = "stylesheet_{$path_info['filename']}";
		} else {
			$description = 'Шаблон';
			$template_name = $path_info['filename'];
		}

		$template_name = str_replace('searchpro_plugin_', '', $template_name);

		$description .= ' плагина "Поиск PRO"';

		if($name = self::getName($template_name)) {
			$description .= " — $name";
		}

		return $description;
	}

	protected function setThemeTemplate(waTheme $theme, $path, $value)
	{
		if($this->isThemeTemplateExists($theme, $path)) {
			$theme->changeFile($path, $this->getThemeTemplateDescription($path));
		} else {
			$theme->addFile($path, $this->getThemeTemplateDescription($path));
		}

		$full_path = $this->getThemeTemplateFullPath($theme, $path);
		waFiles::write($full_path, $value);
	}

	public function get($name = null, $is_return_path = false)
	{
		if($this->templates === null) {
			$this->templates = $this->getTemplates();
		}

		if($name === null) {
			return $this->templates;
		} else {
			$output = null;

			$theme_id = $this->getThemeId();

			$file = self::getTemplateFile($name);

			if(isset($this->templates[$theme_id][$name])) {
				if(!$is_return_path)
					$output = $this->templates[$theme_id][$name];
				else {
					$path = self::getThemeTemplatePath($name);
					$output = ($is_return_path === 'url' ? $this->custom_url : $this->custom_path) . "/$theme_id/{$path}";
				}
			} elseif(isset($this->templates['*'][$name])) {
				if(!$is_return_path)
					$output = $this->templates['*'][$name];
				else {
					if(substr($file, -4) === '.css') {
						$output = ($is_return_path === 'url' ? $this->source_css_url : $this->source_css_path) . "/frontend.{$file}";
					} else {
						if($is_return_path !== 'url')
							$output = "{$this->source_path}/{$file}";
					}
				}
			}

			return $output;
		}
	}

	public function set($templates)
	{
		if(!$templates)
			return;

		$source_templates = $this->getSourceTemplates();

		$updates = array();

		foreach($templates as $theme_id => $_templates) {
			foreach($source_templates as $name => $content) {
				$updates[$theme_id][$name] = ifset($_templates, $name, null);

				if($updates[$theme_id][$name] === $content)
					$updates[$theme_id][$name] = null;
			}
		}

		$this->updateTemplates($updates);
	}

	public static function getFileTemplate($file)
	{
		$path = pathinfo($file);

		switch($path['extension']) {
			case 'html':
				return basename($file, '.html');
				break;
			case 'css':
				if(substr($file, 0, strlen('frontend.')) === 'frontend.')
					$file = substr($file, strlen('frontend.'));

				return 'stylesheet_' . strtolower(basename($file, '.css'));
				break;
		}
	}

	public static function getTemplateFile($name)
	{
		if(substr($name, 0, strlen('stylesheet_')) === 'stylesheet_') {
			$file = substr($name, strpos($name, 'stylesheet_') + strlen('stylesheet_'));
			$file = strtolower($file);

			return "$file.css";
		} else {
			return "$name.html";
		}
	}

	public static function getDirStorefront($storefront)
	{
		return preg_replace('/[:\/\?#\*\s]+/', '_', waIdna::enc($storefront));
	}

	private function updateTemplates($updates)
	{
		foreach($updates as $theme_id => $templates) {
			$theme = $this->getTheme($theme_id);

			foreach($templates as $name => $value) {
				$path = self::getThemeTemplatePath($name);

				if($value === null) {
					if($this->isThemeTemplateExists($theme, $path)) {
						$this->deleteThemeTemplate($theme, $path);
					}
				} else {
					$this->setThemeTemplate($theme, $path, $value);
				}
			}
		}
	}

	private function getTemplates($resource = false)
	{
		$env = self::getEnv();

		if ($env->isFrontend())
		{
			$_theme = $env->getTheme($env->getCurrentTheme());
			$_theme_assoc = array(
				'id' => $_theme->id,
				'name' => $_theme->getName(),
				'instance' => $_theme,
			);
			$themes = array($_theme_assoc);
		}
		else
		{
			$themes = $env->getThemes(true);
		}

		$source_templates = $this->getSourceTemplates($resource);
		$output_templates['*'] = $source_templates;

		foreach($source_templates as $name => $source_content) {
			$path = self::getThemeTemplatePath($name);

			foreach($themes as $_theme) {
				$theme = $_theme['instance'];

				if($this->isThemeTemplateExists($theme, $path, true)) {
					$full_path = $this->getThemeTemplateFullPath($theme, $path);
					$content = file_get_contents($full_path);

					if($resource) {
						$output_templates[$_theme['id']][$name] = array(
							'content' => $content,
							'modified' => filemtime($full_path)
						);
					} else {
						$output_templates[$_theme['id']][$name] = $content;
					}
				}
			}
		}

		return $output_templates;
	}

	public function getSourceTemplates($resource = false)
	{
		if(file_exists($this->source_path) && is_dir($this->source_path)) {
			$source_files = preg_grep('/\.html$/', scandir($this->source_path));

			if(file_exists($this->source_css_path) && is_dir($this->source_css_path))
				$source_files = array_merge($source_files, preg_grep('/\.css$/', scandir($this->source_css_path)));

			$source_templates = array();
			foreach($source_files as $file) {
				if(substr($file, -4) === '.css')
					$path = "{$this->source_css_path}/{$file}";
				else
					$path = "{$this->source_path}/{$file}";

				if(file_exists($path)) {
					$content = file_get_contents($path);
				} else {
					$content = '';
				}

				if($resource) {
					$source_templates[self::getFileTemplate($file)] = array(
						'content' => $content,
						'modified' => filemtime($path)
					);
				} else {
					$source_templates[self::getFileTemplate($file)] = $content;
				}
			}

			return $source_templates;
		} else
			return array();
	}
}
