<?php


class shopSeoGroupCategorySettingsModel extends waModel implements shopSeoGroupCategorySettingsSource
{
	protected $table = 'shop_seo_group_category_settings';
	
	public function getByGroupId($group_id)
	{
		return $this->select('name, value')->where('group_id = ?', $group_id)->query()->fetchAll();
	}
	
	public function updateByGroupId($group_id, $rows)
	{
		$this->deleteByGroupId($group_id);
		
		foreach ($rows as $row)
		{
			$row['group_id'] = $group_id;
			$this->insert($row);
		}
	}
	
	public function deleteByGroupId($group_id)
	{
		$this->deleteByField('group_id', $group_id);
	}
}