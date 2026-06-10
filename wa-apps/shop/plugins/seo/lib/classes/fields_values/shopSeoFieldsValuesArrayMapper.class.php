<?php


class shopSeoFieldsValuesArrayMapper
{
	public function mapFieldsValues(shopSeoFieldsValues $fields_values)
	{
		return $fields_values->getValues();
	}
	
	public function mapArray(shopSeoFieldsValues $fields_values, $fields, $values)
	{
		$_values = $values;
		$values = array();
		
		foreach ($fields as $i => $field)
		{
			if (array_key_exists($i, $_values))
			{
				$values[] = $_values[$i];
			}
			else
			{
				$values[] = '';
			}
		}
		
		$fields_values->setFields($fields);
		$fields_values->setValues($values);
	}
}