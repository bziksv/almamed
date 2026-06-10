<?php

class shopSeofilterPluginProductfiltersSettingsAction
{
	public function getState()
	{
		$general_storefront = shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL;

		$settings = new shopSeofilterProductfiltersSettings($general_storefront);

		return array(
			'storefronts' => $this->allStorefronts(),
			'modified_storefronts' => $this->getModifiedStorefronts(),
			'categories' => $this->getCategories(),
			'categories_sorted' => $this->getCategoriesSorted(),
			'features' => $this->getAllFeatures(),
			'categories_tree' => $this->getCategoriesTree(),
			'edited_storefront' => $general_storefront,

			'categories_settings' => array($general_storefront => $this->getCategoriesSettings()),
			'default_category_settings' => $this->getDefaultCategorySettings(),

			'settings' => array($general_storefront => $settings->getSettings()),
			'category_feature_rules' => array($general_storefront => array()),
		);
	}

	private function getCategoriesSorted()
	{
		$category_model = new shopCategoryModel();
		$full_tree = $category_model->getFullTree();

		$list_ordered = array();
		//$category_names = array();
		foreach ($full_tree as $category)
		{
			$list_ordered[] = array(
				'id' => $category['id'],
				'name' => str_repeat('-', $category['depth'] + 1) . $category['name'],
				'category_is_hidden' => $category['status'] == '0',
			);
			//$category_names[$category['id']] = $category['name'];
		}

		return $list_ordered;
	}

	private function allStorefronts()
	{
		$routing = wa()->getRouting();
		$domains = $routing->getByApp('shop');
		$urls = array();

		foreach ($domains as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if ((!method_exists($routing, 'isAlias') || !$routing->isAlias($domain)) and isset($route['url']))
				{
					$urls[] = $domain . '/' . $route['url'];
				}
			}
		}

		return $urls;
	}

	private function getModifiedStorefronts()
	{
		$settings_model = new shopSeofilterProductfiltersSettingsModel();
		$category_feature_rule_model = new shopSeofilterProductfiltersCategoryFeatureRuleModel();
		$categories_settings_model = new shopSeofilterProductfiltersCategorySettingsModel();

		$modified_storefronts = array();

		$storefronts_from_db = $settings_model
			->select('DISTINCT storefront')
			->where('!(storefront = :general and name = \'is_enabled\')', array('general' => shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL))
			->fetchAll('storefront', true);

		foreach ($storefronts_from_db as $storefront => $_)
		{
			$modified_storefronts[$storefront] = $storefront;
		}

		$storefronts_from_db = $category_feature_rule_model
			->select('DISTINCT storefront')
			->fetchAll('storefront', true);

		foreach ($storefronts_from_db as $storefront => $_)
		{
			$modified_storefronts[$storefront] = $storefront;
		}

		$storefronts_from_db = $categories_settings_model
			->select('DISTINCT storefront')
			->fetchAll('storefront', true);

		foreach ($storefronts_from_db as $storefront => $_)
		{
			$modified_storefronts[$storefront] = $storefront;
		}

		return $modified_storefronts;
	}

	private function getCategoriesTree()
	{
		$tree = array(
			'label' => 'Категории',
			'value' => 0,
			'children' => $this->collectChildren(0),
		);

		return $tree;
	}

	private function collectChildren($category_id)
	{
		$category_model = new shopCategoryModel();

		$children = $category_model->getByField('parent_id', $category_id, true);

		$tree = array();

		foreach ($children as $child)
		{
			$tree[] = array(
				'label' => $child['name'],
				'id' => $child['id'],
				'children' => $this->collectChildren($child['id']),
			);
		}

		return $tree;
	}

	private function getCategories()
	{
		$category_model = new shopCategoryModel();

		$sql = '
select c.*, GROUP_CONCAT(c2.id SEPARATOR \',\') AS children_ids
from shop_category c
left join shop_category c2 on c.id = c2.parent_id
group by c.id
order by c.id
';

		$categories = array();
		foreach ($category_model->query($sql) as $row)
		{
			$row['children_ids'] = strlen($row['children_ids'])
				? explode(',', $row['children_ids'])
				: array();

			$categories[$row['id']] = $row;
		}

		return $categories;
	}

	private function getAllFeatures()
	{
		$feature_model = new shopFeatureModel();

		return $feature_model->select('id, code, name')->fetchAll('id');
	}

	private function getCategoriesSettings()
	{
		$model = new shopSeofilterProductfiltersCategorySettingsModel();

		return $model->getCategoriesSettings(shopSeofilterProductfiltersSettings::STOREFRONT_GENERAL);
	}

	private function getDefaultCategorySettings()
	{
		$model = new shopSeofilterProductfiltersCategorySettingsModel();

		return $model->getDefaultSettings();
	}
}