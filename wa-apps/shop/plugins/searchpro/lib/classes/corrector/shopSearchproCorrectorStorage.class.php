<?php

class shopSearchproCorrectorStorage
{
	private $path;
	private $data;

	public function __construct($type)
	{
		$this->path = wa()->getAppPath("plugins/searchpro/lib/config/data/corrector/$type.php", 'shop');

		if(file_exists($this->path)) {
			$this->data = include($this->path);

			if(!is_array($this->data)) {
				$this->data = array();
			}
		} else {
			$this->data = array();
		}
	}

	public function getData($name = null, $default = null)
	{
		if($name === null) {
			return $this->data;
		} else {
			if(strpos($name, '->') !== false) {
				$value = substr($name, strpos($name, '->') + 2);
				$name = substr($name, 0, strpos($name, '->'));

				return ifset($this->data, $name, $value, $default);
			} else
				return ifset($this->data, $name, $default);
		}
	}
}