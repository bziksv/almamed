<?php


class shopSeoCategoryFieldValueModel extends waModel implements shopSeoCategoryFieldsValuesSource
{
	protected $table = 'shop_seo_category_field_value';
	
	public function getByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id)
	{
		return $this->getByField(array(
			'group_storefront_id' => $group_storefront_id,
			'category_id' => $category_id,
		), true);
	}
	
	public function updateByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id, $rows)
	{
		$this->deleteByField(array(
			'group_storefront_id' => $group_storefront_id,
			'category_id' => $category_id,
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
	
	public function deleteByCategoryId($category_id)
	{
		$this->deleteByField('category_id', $category_id);
	}
	
	public function deleteByFieldId($field_id)
	{
		$this->deleteByField('field_id', $field_id);
	}
}