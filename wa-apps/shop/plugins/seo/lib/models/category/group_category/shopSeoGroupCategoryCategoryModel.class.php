<?php


class shopSeoGroupCategoryCategoryModel extends waModel implements shopSeoGroupCategoryCategorySource
{
	protected $table = 'shop_seo_group_category_category';
	
	public function getByGroupId($id)
	{
		return $this->getByField('group_id', $id, true);
	}
	
	public function updateByGroupId($id, $rows)
	{
		$this->deleteByGroupId($id);
		
		foreach ($rows as $row)
		{
			$this->insert($row);
		}
	}
	
	public function deleteByGroupId($id)
	{
		$this->deleteByField('group_id', $id);
	}
}