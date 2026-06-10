<?php


class shopSeoProductSettingsService
{
	private $settings_source;
	private $settings = array();
	
	public function __construct(shopSeoProductSettingsSource $settings_source)
	{
		$this->settings_source = $settings_source;
	}
	
	/**
	 * @param $group_storefront_id
	 * @param $product_id
	 * @return shopSeoProductSettings
	 */
	public function getByGroupStorefrontIdAndProductId($group_storefront_id, $product_id)
	{
		$key = json_encode(array('group_storefront_id' => $group_storefront_id, 'product_id' => $product_id));
		
		if (!isset($this->settings[$key]))
		{
			$rows = $this->settings_source->getByGroupStorefrontIdAndProductId($group_storefront_id, $product_id);
			$settings = array();
			
			foreach ($rows as $row)
			{
				$settings[$row['name']] = $row['value'];
			}
			
			$product_settings = new shopSeoProductSettings();
			$product_settings->setGroupStorefrontId($group_storefront_id);
			$product_settings->setProductId($product_id);
			
			$product_settings->setSettings($settings);
			
			$this->settings[$key] = $product_settings;
		}
		
		return $this->settings[$key];
	}
	
	public function getGeneralByProductId($product_id)
	{
		return $this->getByGroupStorefrontIdAndProductId(0, $product_id);
	}
	
	public function store(shopSeoProductSettings $product_settings)
	{
		$this->settings = array();
		$settings = $product_settings->getSettings();
		$rows = array();
		
		foreach ($settings as $name => $value)
		{
			$rows[] = array('name' => $name, 'value' => $value);
		}
		
		$this->settings_source->updateByGroupStorefrontIdAndProductId($product_settings->getGroupStorefrontId(), $product_settings->getProductId(), $rows);
	}
	
	public function delete(shopSeoProductSettings $product_settings)
	{
		$this->settings = array();
		$this->settings_source->deleteByGroupStorefrontIdAndProductId($product_settings->getGroupStorefrontId(), $product_settings->getProductId());
	}
	
	public function deleteByGroupStorefrontId($group_storefront_id)
	{
		$this->settings = array();
		$this->settings_source->deleteByGroupStorefrontId($group_storefront_id);
	}
	
	public function deleteByProductId($product_id)
	{
		$this->settings = array();
		$this->settings_source->deleteByProductId($product_id);
	}
}