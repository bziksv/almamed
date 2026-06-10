<?php


class shopSeoProductSettingsModel extends waModel implements shopSeoProductSettingsSource
{
	protected $table = 'shop_seo_product_settings';
	
	public function getByGroupStorefrontIdAndProductId($group_storefront_id, $product_id)
	{
		return $this->select('name, value')->where(
			'group_storefront_id  = ? && product_id = ?', $group_storefront_id, $product_id)
			->query()->fetchAll();
	}
	
	public function updateByGroupStorefrontIdAndProductId($group_storefront_id, $product_id, $rows)
	{
		$this->deleteByGroupStorefrontIdAndProductId($group_storefront_id, $product_id);
		
		foreach ($rows as $row)
		{
			$row['group_storefront_id'] = $group_storefront_id;
			$row['product_id'] = $product_id;
			$this->insert($row);
		}
	}
	
	public function deleteByGroupStorefrontIdAndProductId($group_storefront_id, $product_id)
	{
		$this->deleteByField(array(
			'group_storefront_id' => $group_storefront_id,
			'product_id' => $product_id,
		));
	}
	
	public function deleteByGroupStorefrontId($group_storefront_id)
	{
		$this->deleteByField(array(
			'group_storefront_id' => $group_storefront_id,
		));
	}
	
	public function deleteByProductId($product_id)
	{
		$this->deleteByField(array(
			'product_id' => $product_id,
		));
	}
}