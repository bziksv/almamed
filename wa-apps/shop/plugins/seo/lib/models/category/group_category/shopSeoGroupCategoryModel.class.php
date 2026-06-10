<?php


class shopSeoGroupCategoryModel extends waModel implements shopSeoGroupCategorySource
{
	protected $table = 'shop_seo_group_category';
	
	public function getByGroupId($id)
	{
		return $this->getById($id);
	}
	
	public function getAllGroups()
	{
		return $this->getAll();
	}
	
	public function getByStorefrontAndCategoryId($storefront, $category_id)
	{
		return $this->query("
select * from shop_seo_group_category gc
where (storefront_select_rule_type = 'ANY'
	|| (storefront_select_rule_type = 'INCLUDE' && :storefront in (
	  	select storefront from shop_seo_group_category_storefront gcs
	  		where gc.id = gcs.group_id
	))
	|| (storefront_select_rule_type = 'EXCLUDE' && :storefront not in (
	  	select storefront from shop_seo_group_category_storefront gcs
	  		where gc.id = gcs.group_id
	))) && (category_select_rule_type = 'ANY'
	|| (category_select_rule_type = 'INCLUDE' && :category_id in (
	  	select category_id from shop_seo_group_category_category gcc
	  		where gc.id = gcc.group_id
	))
	|| (category_select_rule_type = 'EXCLUDE' && :category_id not in (
	  	select category_id from shop_seo_group_category_category gcc
	  		where gc.id = gcc.group_id
	)))
order by sort asc
",
			array('storefront' => $storefront, 'category_id' => $category_id))->fetchAll();
	}
	
	/**
	 * @param $row
	 * @return int
	 */
	public function addGroup($row)
	{
		return $this->insert($row);
	}
	
	public function updateGroup($id, $row)
	{
		$this->updateById($id, $row);
	}
	
	public function updateSort()
	{
		$sort = 0;
		
		foreach ($this->select('*')->order('sort asc') as $row)
		{
			$row['sort'] = $sort++;
			$this->replace($row);
		}
	}
	
	public function deleteGroup($id)
	{
		$this->deleteById($id);
	}
}