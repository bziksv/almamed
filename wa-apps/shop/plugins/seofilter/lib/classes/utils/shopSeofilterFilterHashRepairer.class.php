<?php

class shopSeofilterFilterHashRepairer
{
	public function repair()
	{
		$model = new shopSeofilterFilterModel();

		$hashes = array();
		$duplicated_hashes = array();

		foreach ($model->select('id')->query() as $row)
		{
			$filter_id = $row['id'];
			$filter = new shopSeofilterFilter(array('id' => $filter_id));

			$params = $filter->getFeatureValuesAsFilterParams(shopSeofilterFilter::PARAMS_WITHOUT_RANGE_VALUES);

			$hash = shopSeofilterFilterFeatureValuesHelper::hash($params);

			$model->updateById($filter_id, array(
				'feature_value_hash' => $hash,
			));

			if ($hash)
			{
				if (isset($hashes[$hash]))
				{
					if (!isset($duplicated_hashes[$hash]))
					{
						$duplicated_hashes[$hash] = array($hashes[$hash]);
					}

					$duplicated_hashes[$hash][] = $filter_id;
				}
				else
				{
					$hashes[$hash] = $filter_id;
				}
			}
		}

		return $duplicated_hashes;
	}
}