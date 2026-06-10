<?php

/**
 * Класс для рендера кастомных стилей в CSS-файл
 */
class shopSearchproStylesheet
{
	protected $fields = array(
		'field', 'page'
	);

	private $settings;
	private $view;

	protected static $env;

	public function __construct($settings)
	{
		$this->settings = $settings;
	}

	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopSearchproEnv();

		return self::$env;
	}

	private static function getOutputPath($theme_id, $asset)
	{
		$dir_path = wa()->getDataPath('plugins/searchpro/stylesheet', true, 'shop', true);

		return "$dir_path/$theme_id/$asset.css";
	}

	public static function getOutputUrl($theme_id, $asset)
	{
		$output_path = self::getOutputPath($theme_id, $asset);

		if(file_exists($output_path)) {
			$modified_time = filemtime($output_path);

			$url_path = wa()->getDataUrl('plugins/searchpro/stylesheet', true, 'shop', true);

			return "$url_path/$theme_id/$asset.css?$modified_time";
		}

		return null;
	}

	private function getRenderPath($asset)
	{
		$dir_path = wa()->getAppPath('plugins/searchpro/templates/render/stylesheet', 'shop');

		return "$dir_path/$asset.html";
	}

	private function getView()
	{
		if(!isset($this->view))
			$this->view = wa()->getView();

		return $this->view;
	}

	private function save($theme_id, $asset, $stylesheet)
	{
		$stylesheet = trim($stylesheet);

		$path = self::getOutputPath($theme_id, $asset);

		if(!$stylesheet) {
			if(file_exists($path)) {
				waFiles::delete($path);
			}

			return false;
		}

		waFiles::create($path);

		@file_put_contents($path, $stylesheet);
	}

	public function render()
	{
		foreach($this->fields as $asset) {
			$view = $this->getView();

			foreach($this->getEnv()->getThemes() as $theme) {
				$view->assign('settings', ifset($this->settings, $theme['id'], null));
				$stylesheet = $view->fetch($this->getRenderPath($asset));

				$this->save($theme['id'], $asset, $stylesheet);
			}
		}
	}
}