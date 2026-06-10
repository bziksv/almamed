<?php


class shopSeoSettingsArrayMapper
{
	public function mapSettings(shopSeoSettings $settings)
	{
		$result = array();
		
		foreach ($settings->getMetaData() as $name => $meta_data)
		{
			$result[$name] = $settings->{$name};
		}
		
		return $result;
	}
	
	public function mapArray(shopSeoSettings $settings, $array)
	{
		foreach ($settings->getMetaData() as $name => $meta_data)
		{
			if (!array_key_exists($name, $array))
			{
				continue;
			}
			
			$settings->{$name} = $array[$name];
		}
	}
}