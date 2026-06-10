<?php

class shopSeofilterFilterFeatureValueChecker
{
	public function getInvalidFilterIds()
	{
		$feature_model = new shopFeatureModel();

		$feature_values_sql = '
SELECT
	fv.feature_id,
	fv.value_id,
	GROUP_CONCAT(DISTINCT fv.filter_id SEPARATOR \',\') AS filter_ids
FROM shop_seofilter_filter_feature_value AS fv
GROUP BY fv.feature_id, fv.value_id
';

		$features = array();
		$invalid_filter_ids = array();

		$filter_feature_values = $feature_model->query($feature_values_sql)->fetchAll();

		foreach ($filter_feature_values as $row)
		{
			$filter_feature_id = $row['feature_id'];
			$filter_value_id = $row['value_id'];

			if (!array_key_exists($filter_feature_id, $features))
			{
				$feature = $feature_model->getById($filter_feature_id);
				$features[$filter_feature_id] = $feature;

				if (is_array($feature))
				{
					$tmp = $feature_model->getValues(array($feature));
					$features[$filter_feature_id] = $tmp[0];
				}
			}

			$feature = $features[$filter_feature_id];

			if (!$feature)
			{
				foreach (explode(',', $row['filter_ids']) as $filter_id)
				{
					$invalid_filter_ids[$filter_id] = true;
				}

				continue;
			}

			if (!isset($feature['values'][$filter_value_id]))
			{
				foreach (explode(',', $row['filter_ids']) as $filter_id)
				{
					$invalid_filter_ids[$filter_id] = true;
				}

				continue;
			}
		}

		return array_keys($invalid_filter_ids);
	}
}