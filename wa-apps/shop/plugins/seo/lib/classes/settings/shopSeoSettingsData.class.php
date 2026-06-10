<?php


class shopSeoSettingsData
{
	const TYPE_TEXT = 'TEXT';
	const TYPE_BOOLEAN = 'BOOLEAN';
	const TYPE_NUMBER = 'BOOLEAN';
	const TYPE_JSON = 'JSON';
	
	private static $default_type = self::TYPE_TEXT;
	private $meta_data;
	private $settings;
	/** @var shopSeoSettingsData|null */
	private $parent;
	
	public function __construct($meta_data)
	{
		$this->meta_data = $meta_data;
		$this->settings = array();
		$this->parent = null;
	}
	
	public function __get($name)
	{
		return $this->handleGetSetting($name);
	}
	
	public function __set($name, $value)
	{
		$this->handleSetSetting($name, $value);
	}
	
	public function getMetaData()
	{
		return $this->meta_data;
	}
	
	public function getSettings()
	{
		return $this->settings;
	}
	
	public function setSettings($settings)
	{
		$this->settings = array();
		
		foreach ($settings as $name => $value)
		{
			$this->handleSetSetting($name, $value);
		}
	}
	
	public function getParent()
	{
		return $this->parent;
	}
	
	public function setParent($parent)
	{
		$this->parent = $parent;
	}
	
	private function handleGetSetting($name)
	{
		if (!array_key_exists($name, $this->meta_data))
		{
			return null;
		}
		
		$meta_data = $this->meta_data[$name];
		$default = null;
		
		if (array_key_exists('default', $meta_data))
		{
			$default = $meta_data['default'];
		}
		
		if (!is_null($this->parent))
		{
			$default = $this->parent->__get($name);
		}
		
		if (!array_key_exists($name, $this->settings))
		{
			return $default;
		}
		
		$type = self::$default_type;
		
		if (array_key_exists('type', $meta_data))
		{
			$type = $meta_data['type'];
		}
		
		$value = $this->settings[$name];
		
		if ($type === self::TYPE_TEXT)
		{
			return (string)$value;
		}
		elseif ($type === self::TYPE_BOOLEAN)
		{
			return (bool)$value;
		}
		elseif ($type === self::TYPE_NUMBER)
		{
			return (double)$value;
		}
		elseif ($type === self::TYPE_JSON)
		{
			return json_decode($value, true);
		}
		else
		{
			throw new InvalidArgumentException('Unknown type');
		}
	}
	
	private function handleSetSetting($name, $value)
	{
		if (!array_key_exists($name, $this->meta_data))
		{
			return;
		}
		
		$meta_data = $this->meta_data[$name];
		$default = null;
		
		if (array_key_exists('default', $meta_data))
		{
			$default = $meta_data['default'];
		}
		
		if (!is_null($this->parent))
		{
			$default = $this->parent->__get($name);
		}
		
		$type = self::$default_type;
		
		if (array_key_exists('type', $meta_data))
		{
			$type = $meta_data['type'];
		}
		
		if ($type === self::TYPE_TEXT)
		{
			$value = (string)$value;
		}
		elseif ($type === self::TYPE_BOOLEAN)
		{
			$value = (bool)$value;
		}
		elseif ($type === self::TYPE_NUMBER)
		{
			$value = (double)$value;
		}
		elseif ($type === self::TYPE_JSON)
		{
		
		}
		else
		{
			throw new InvalidArgumentException('Unknown type');
		}
		
		if ($default === $value)
		{
			unset($this->settings[$name]);
			return;
		}
		
		if ($type === self::TYPE_BOOLEAN)
		{
			$value = $value ? 1 : 0;
		}
		elseif ($type === self::TYPE_JSON)
		{
			$value = json_encode($value);
		}
		
		$this->settings[$name] = $value;
	}
}