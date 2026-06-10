<?php


abstract class shopSeoFieldModel extends waModel implements shopSeoFieldSource
{
	public function getFieldsRows()
	{
		return $this->select('*')->order('id asc')->fetchAll();
	}
	
	public function getFieldRow($field_id)
	{
		return $this->getById($field_id);
	}
	
	public function updateFieldsRows($rows)
	{
		$this->truncate();
		
		foreach ($rows as $row)
		{
			if (isset($row['id']))
			{
				$this->insert($row);
			}
		}
		
		foreach ($rows as $row)
		{
			if (!isset($row['id']))
			{
				$this->insert($row);
			}
		}
	}
	
	public function addField($row)
	{
		return $this->insert($row);
	}
	
	public function updateField($field_id, $row)
	{
		$this->updateById($field_id, $row);
	}
	
	public function deleteField($field_id)
	{
		$this->deleteById($field_id);
	}
}