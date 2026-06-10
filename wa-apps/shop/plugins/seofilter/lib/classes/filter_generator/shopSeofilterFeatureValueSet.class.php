<?php

class shopSeofilterFeatureValueSet
{
	private $feature_value_groups = array();

	/**
	 * @param array $feature_value_group
	 */
	public function add($feature_value_group)
	{
		$this->feature_value_groups[$this->getGroupKey($feature_value_group)] = $feature_value_group;
	}

	/**
	 * @param shopSeofilterFeatureValueSet $set
	 * @return shopSeofilterFeatureValueSet
	 */
	public function combine(shopSeofilterFeatureValueSet $set)
	{
		$result_set = new shopSeofilterFeatureValueSet();

		foreach ($this->feature_value_groups as $group_left)
		{
			foreach ($set->feature_value_groups as $group_right)
			{
				if ($this->getGroupKey($group_left) == $this->getGroupKey($group_right))
				{
					continue;
				}

				$result_set->add($this->combineGroups($group_left, $group_right));
			}
		}

		return $result_set;
	}

	public function getGroups()
	{
		return $this->feature_value_groups;
	}

	private function combineGroups($group_left, $group_right)
	{
		return shopSeofilterFilterFeatureValuesHelper::arrayMergeRecursive($group_left, $group_right);
	}

	private function getGroupKey($group)
	{
		$features = array_keys($group);
		sort($features);

		$result = array();

		foreach ($features as $feature)
		{
			$values = array_keys($group[$feature]);
			sort($values);

			$result[] = $feature . '/' . implode(',', $values);
		}

		return implode('|', $result);
	}
}
