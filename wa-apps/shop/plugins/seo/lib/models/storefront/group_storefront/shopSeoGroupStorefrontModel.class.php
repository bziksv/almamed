<?php


class shopSeoGroupStorefrontModel extends waModel implements shopSeoGroupStorefrontSource
{
	protected $table = 'shop_seo_group_storefront';
	
	public function getByGroupId($id)
	{
		return $this->getById($id);
	}
	
	public function getAllGroups()
	{
		return $this->getAll();
	}
	
	public function getByStorefront($storefront)
	{
		return $this->query("
select * from shop_seo_group_storefront gs
where storefront_select_rule_type = 'ANY'
	|| (storefront_select_rule_type = 'INCLUDE' && :storefront in (
	  	select storefront from shop_seo_group_storefront_storefront gss
	  		where gs.id = gss.group_id
	))
	|| (storefront_select_rule_type = 'EXCLUDE' && :storefront not in (
	  	select storefront from shop_seo_group_storefront_storefront gss
	  		where gs.id = gss.group_id
	))
order by sort asc
",
			array('storefront' => $storefront))->fetchAll();
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