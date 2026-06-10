<?php


class shopSeoCategorySettingsModel extends waModel implements shopSeoCategorySettingsSource
{
	protected $table = 'shop_seo_category_settings';
	
	public function getByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id)
	{
		return $this->select('name, value')->where(
			'group_storefront_id  = ? && category_id = ?', $group_storefront_id, $category_id)
			->query()->fetchAll();
	}
	
	public function updateByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id, $rows)
	{
		$this->deleteByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id);
		
		foreach ($rows as $row)
		{
			$row['group_storefront_id'] = $group_storefront_id;
			$row['category_id'] = $category_id;
			$this->insert($row);
		}
	}
	
	public function deleteByGroupStorefrontIdAndCategoryId($group_storefront_id, $category_id)
	{
		$this->deleteByField(array(
			'group_storefront_id' => $group_storefront_id,
			'category_id' => $category_id,
		));
	}
	
	public function deleteByGroupStorefrontId($group_storefront_id)
	{
		$this->deleteByField(array(
			'group_storefront_id' => $group_storefront_id,
		));
	}
	
	public function deleteByCategoryId($category_id)
	{
		$this->deleteByField(array(
			'category_id' => $category_id,
		));
	}
}