<?php


abstract class shopSeoFieldService
{
	private $field_source;
	
	public function __construct(shopSeoFieldSource $field_source)
	{
		$this->field_source = $field_source;
	}
	
	/**
	 * @return shopSeoField[]
	 */
	public function getFields()
	{
		$rows = $this->field_source->getFieldsRows();
		$fields = array();
		
		foreach ($rows as $row)
		{
			$fields[] = $this->fromRow($row);
		}
		
		return $fields;
	}
	
	public function getField($field_id)
	{
		$row = $this->field_source->getFieldRow($field_id);
		
		if (is_null($row))
		{
			return null;
		}
		
		return $this->fromRow($row);
	}
	
	public function correctFieldsValues(shopSeoFieldsValues $fields_values)
	{
		foreach ($fields_values->getFields() as $field)
		{
			$_field = $this->getField($field->getId());
			
			if (is_null($_field))
			{
				$fields_values->deleteField($field);
			}
		}
	}
	
	public function store(shopSeoField $field)
	{
		$row = $this->toRow($field);
		
		if ($field->getId())
		{
			$this->field_source->updateField($field->getId(), $row);
		}
		else
		{
			$id = $this->field_source->addField($row);
			$field->setId($id);
		}
	}
	
	public function delete(shopSeoField $field)
	{
		if (!$field->getId())
		{
			return;
		}
		
		$this->field_source->deleteField($field->getId());
		$field->setId(null);
	}
	
	private function fromRow($row)
	{
		$field = new shopSeoField();
		$field->setId($row['id']);
		$field->setName($row['name']);
		
		return $field;
	}
	
	private function toRow(shopSeoField $field)
	{
		return array(
			'id' => $field->getId(),
			'name' => $field->getName(),
		);
	}
}