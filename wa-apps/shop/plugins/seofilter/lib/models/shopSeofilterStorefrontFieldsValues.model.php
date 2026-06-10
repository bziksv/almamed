<?php

class shopSeofilterStorefrontFieldsValuesModel extends waModel
{
	protected $table = 'shop_seofilter_storefront_fields_values';

	public function getForStorefront($storefront, $context = shopSeofilterDefaultTemplateModel::CONTEXT_DEFAULT)
	{
		$rows = $this->getByField(array(
			'storefront' => $storefront,
			'context' => $context,
		), true);

		$result = array();
		foreach ($rows as $row)
		{
			$result[$row['field_id']] = $row['value'];
		}

		return $result;
	}
}