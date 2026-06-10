<?php


class shopSeoFieldArrayMapper
{
	public function mapField(shopSeoField $field)
	{
		return array(
			'id' => $field->getId(),
			'name' => $field->getName(),
		);
	}
	
	/**
	 * @param shopSeoField[] $fields
	 * @return array
	 */
	public function mapFields($fields)
	{
		$result = array();
		
		foreach ($fields as $field)
		{
			$result[] = $this->mapField($field);
		}
		
		return $result;
	}
	
	public function mapArray($array)
	{
		$field = new shopSeoField();
		$field->setId($array['id']);
		$field->setName($array['name']);
		
		return $field;
	}
	
	public function mapArrays($arrays)
	{
		$result = array();
		
		foreach ($arrays as $array)
		{
			$result[] = $this->mapArray($array);
		}
		
		return $result;
	}
}