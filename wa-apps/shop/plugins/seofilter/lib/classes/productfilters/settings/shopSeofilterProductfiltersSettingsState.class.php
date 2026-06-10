<?php

class shopSeofilterProductfiltersSettingsState
{
	public function save($state)
	{
		$storefront_settings = $state['settings'];
		$storefront_categories_settings = $state['categories_settings'];

		$storefront_category_feature_rules = $state['category_feature_rules'];
		$storefront_apply_rule_to_subcategories = $state['apply_rule_to_subcategories'];
		$storefront_ignore_inherited = $state['ignore_inherited'];


		$settings_model = new shopSeofilterProductfiltersSettingsModel();

		foreach ($storefront_settings as $storefront => $settings)
		{
			if ($storefront !== shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL)
			{
				unset($settings['is_enabled']);
			}

			$settings_model->saveSettings($storefront, $settings);
		}


		$categories_settings_model = new shopSeofilterProductfiltersCategorySettingsModel();

		foreach ($storefront_categories_settings as $storefront => $categories_settings)
		{
			$categories_settings_model->saveCategoriesSettings($storefront, $categories_settings);
		}




		$category_model = new shopCategoryModel();


		$sql = '
select c.*, GROUP_CONCAT(c2.id SEPARATOR \',\') AS children_ids
from shop_category c
left join shop_category c2 on c.id = c2.parent_id
group by c.id
order by c.left_key
';

		$categories_tree = array();
		foreach ($category_model->query($sql) as $row)
		{
			$row['children_ids'] = strlen($row['children_ids'])
				? explode(',', $row['children_ids'])
				: array();

			//$categories_tree[$row['id']] = $row;
			$categories_tree[] = $row;
		}

		$rules_model = new shopSeofilterProductfiltersCategoryFeatureRuleModel();
		foreach ($storefront_category_feature_rules as $storefront => $category_feature_rules)
		{
			$apply_rule_to_subcategories = ifset($storefront_apply_rule_to_subcategories[$storefront], array());
			$ignore_inherited = ifset($storefront_ignore_inherited[$storefront], array());

			//foreach ($categories_tree as $category_id => $category)
			foreach ($categories_tree as $category_index => $category)
			{
				$category_id = $category['id'];
				if (isset($apply_rule_to_subcategories[$category_id]) && $apply_rule_to_subcategories[$category_id])
				{
					$rule = ifset($category_feature_rules[$category_id]);

					for ($i = $category_index; $i < count($categories_tree); $i++)
					{
						$iterated_category = $categories_tree[$i];
						$iterated_category_id = $iterated_category['id'];

						if ($iterated_category['right_key'] > $category['right_key'])
						{
							break;
						}

						if (!$ignore_inherited[$iterated_category_id])
						{
							if ($rule)
							{
								$rules_model->saveRule($storefront, $iterated_category_id, $rule);
							}

							unset($category_feature_rules[$iterated_category_id]);
						}
					}
				}

				if (isset($category_feature_rules[$category_id]))
				{
					$rule = $category_feature_rules[$category_id];

					$rules_model->saveRule($storefront, $category_id, $rule);
				}
			}

			//foreach ($category_feature_rules as $category_id => $rule)
			//{
			//	$rules_model->saveRule($storefront, $category_id, $rule);
			//}
		}
	}
}