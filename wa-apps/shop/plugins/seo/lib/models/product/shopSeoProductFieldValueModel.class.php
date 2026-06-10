<?php


class shopSeoProductFieldValueModel extends waModel implements shopSeoProductFieldsValuesSource
{
	protected $table = 'shop_seo_product_field_value';
	
	public function getByGroupStorefrontIdAndProductId($group_storefront_id, $product_id)
	{
		return $this->getByField(array(
			'group_storefront_id' => $group_storefront_id,
			'product_id' => $product_id,
		), true);
	}
	
	public function updateByGroupStorefrontIdAndProductId($group_storefront_id, $product_id, $rows)
	{
		$this->deleteByField(array(
			'group_storefront_id' => $group_storefront_id,
			'product_id' => $product_id,
		));
		
		foreach ($rows as $row)
		{
			$this->insert($row);
		}
	}
	
	public function deleteByGroupStorefrontId($group_storefront_id)
	{
		$this->deleteByField('group_storefront_id', $group_storefront_id);
	}
	
	public function deleteByProductId($product_id)
	{
		$this->deleteByField('product_id', $product_id);
	}
	
	public function deleteByFieldId($field_id)
	{
		$this->deleteByField('field_id', $field_id);
	}
}