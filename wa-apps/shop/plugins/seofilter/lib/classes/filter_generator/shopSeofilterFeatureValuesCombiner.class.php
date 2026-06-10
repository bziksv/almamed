<?php

class shopSeofilterFeatureValuesCombiner
{
	public static function combine($filter_features)
	{
		$value_sets = array();
		foreach ($filter_features as $data)
		{
			$feature_id = $data['feature_id'];
			$value_ids = $data['value_ids'];

			$value_set = new shopSeofilterFeatureValueSet();
			foreach ($value_ids as $value_id)
			{
				$feature_value = array(
					$feature_id => array($value_id => 1),
				);
				$value_set->add($feature_value);
			}

			$value_sets[] = $value_set;
		}

		return self::combineSets($value_sets);
	}

	/**
	 * @param shopSeofilterFeatureValueSet[] $sets
	 * @return shopSeofilterFeatureValueSet
	 */
	private static function combineSets($sets)
	{
		$set_1 = array_shift($sets);

		if (count($sets) == 0)
		{
			return $set_1;
		}

		$set_2 = array_shift($sets);

		array_unshift($sets, $set_1->combine($set_2));

		return self::combineSets($sets);
	}
}