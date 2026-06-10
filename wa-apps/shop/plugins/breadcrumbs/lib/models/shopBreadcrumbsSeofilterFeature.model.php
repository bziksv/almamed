<?php

class shopBreadcrumbsSeofilterFeatureModel extends waModel
{
	protected $table = 'shop_breadcrumbs_seofilter_feature';

	public function set($storefront, $feature_ids)
	{
		if (!is_array($feature_ids))
		{
			throw new waException();
		}

		$this->deleteByField('storefront', $storefront);

		foreach ($feature_ids as $sort => $feature_id)
		{
			$data = array(
				'storefront' => $storefront,
				'feature_id' => $feature_id,
				'sort' => $sort,
			);

			$this->insert($data, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
	}
}