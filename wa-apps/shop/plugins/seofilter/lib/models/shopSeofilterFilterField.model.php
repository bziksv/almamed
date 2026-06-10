<?php

class shopSeofilterFilterFieldModel extends waModel
{
	protected $table = 'shop_seofilter_filter_field';

	public static function getAllFields()
	{
		$model = new self();

		return $model
			->select('id,name')
			->order('sort ASC')
			->fetchAll('id', true);
	}
}