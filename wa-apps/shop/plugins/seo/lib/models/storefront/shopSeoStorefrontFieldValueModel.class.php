<?php


class shopSeoStorefrontFieldValueModel extends waModel implements shopSeoStorefrontFieldsValuesSource
{
	protected $table = 'shop_seo_storefront_field_value';
	
	public function getByGroupId($group_id)
	{
		return $this->getByField('group_id', $group_id, true);
	}
	
	public function updateByGroupId($group_id, $rows)
	{
		$this->deleteByGroupId($group_id);
		
		foreach ($rows as $row)
		{
			$this->replace($row);
		}
	}
	
	public function deleteByFieldId($field_id)
	{
		$this->deleteByField('field_id', $field_id);
	}
	
	public function deleteByGroupId($group_id)
	{
		$this->deleteByField('group_id', $group_id);
	}
}