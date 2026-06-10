<?php

class shopSeofilterFilterFieldValueModel extends waModel
{
	protected $table = 'shop_seofilter_filter_field_value';

	public function setFilterValues($filter_id, $field_values)
	{
		foreach ($field_values as $field_id => $field_value)
		{
			$this->insert(array(
				'filter_id' => $filter_id,
				'field_id' => $field_id,
				'value' => $field_value,
			), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
	}
}