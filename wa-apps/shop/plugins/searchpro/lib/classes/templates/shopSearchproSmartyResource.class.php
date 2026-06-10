<?php

class shopSearchproSmartyResource extends Smarty_Resource_Custom
{
	protected $templates;
	protected $theme_id;

	public function __construct(array $templates, $theme_id) {
		$this->templates = $templates;
		$this->theme_id = $theme_id;
	}

	protected function fetch($name, &$source, &$mtime)
	{
		$source = $this->getSource($name);
		$mtime = $this->getModifiedTime($name);
	}

	protected function fetchTimestamp($name) {
		return $this->getModifiedTime($name);
	}

	private function getSource($name)
	{
		$template = $this->getTemplate($name);

		return $template['content'];
	}

	private function getModifiedTime($name)
	{
		$template = $this->getTemplate($name);

		return $template['modified'];
	}

	private function getTemplate($initial_name)
	{
		if(strpos($initial_name, '/') !== false) {
			list($theme_id, $name) = explode('/', $initial_name);
		} else {
			$name = $initial_name;
			$theme_id = $this->theme_id;
		}

		if(isset($this->templates[$theme_id][$name])) {
			return $this->templates[$theme_id][$name];
		} elseif(isset($this->templates['*'][$name])) {
			$template = $this->templates['*'][$name];
			$template['is_global'] = true;

			return $template;
		}

		return null;
	}
}