<?php


class shopSeoGroupCategoryArrayMapper
{
	private $settings_array_mapper;
	private $fields_values_array_mapper;
	
	public function __construct(
		shopSeoSettingsArrayMapper $settings_array_mapper,
		shopSeoFieldsValuesArrayMapper $fields_values_array_mapper
	) {
		$this->settings_array_mapper = $settings_array_mapper;
		$this->fields_values_array_mapper = $fields_values_array_mapper;
	}
	
	public function mapGroupCategory(shopSeoGroupCategory $group_category)
	{
		$result = array(
			'id' => $group_category->getId(),
			'name' => $group_category->getName(),
			'storefront_select_rule_type' => $group_category->getStorefrontSelectRuleType(),
			'storefront_select_rule_variants' => $group_category->getStorefrontSelectRuleVariants(),
			'category_select_rule_type' => $group_category->getCategorySelectRuleType(),
			'category_select_rule_variants' => $group_category->getCategorySelectRuleVariants(),
			'sort' => $group_category->getSort(),
			'settings' => null,
			'fields_values' => null,
		);
		
		if ($group_category->getSettings())
		{
			$result['settings'] = $this->settings_array_mapper->mapSettings($group_category->getSettings());
		}
		
		if ($group_category->getFieldsValues())
		{
			$result['fields_values'] = $this->fields_values_array_mapper->mapFieldsValues(
				$group_category->getFieldsValues()
			);
		}
		
		return $result;
	}
	
	/**
	 * @param shopSeoGroupCategory[] $groups_categories
	 * @return array
	 */
	public function mapGroupsCategories($groups_categories)
	{
		$result = array();
		
		foreach ($groups_categories as $group_category)
		{
			$result[] = $this->mapGroupCategory($group_category);
		}
		
		return $result;
	}
	
	/**
	 * @param $group_category_array
	 * @param shopSeoField[] $fields
	 * @return shopSeoGroupCategory
	 */
	public function mapArray($group_category_array, $fields)
	{
		$group_category = new shopSeoGroupCategory();
		$group_category->setId($group_category_array['id']);
		$group_category->setName($group_category_array['name']);
		$group_category->setStorefrontSelectRuleType($group_category_array['storefront_select_rule_type']);
		$group_category->setStorefrontSelectRuleVariants($group_category_array['storefront_select_rule_variants']);
		$group_category->setCategorySelectRuleType($group_category_array['category_select_rule_type']);
		$group_category->setCategorySelectRuleVariants($group_category_array['category_select_rule_variants']);
		$group_category->setSort($group_category_array['sort']);
		
		if ($group_category_array['settings'])
		{
			$settings = new shopSeoGroupCategorySettings();
			$settings->setGroupId($group_category->getId());
			$this->settings_array_mapper->mapArray($settings, $group_category_array['settings']);
			$group_category->setSettings($settings);
		}
		
		if ($group_category_array['fields_values'])
		{
			$fields_values = new shopSeoGroupCategoryFieldsValues();
			$fields_values->setGroupId($group_category->getId());
			$this->fields_values_array_mapper->mapArray($fields_values, $fields, $group_category_array['fields_values']);
			$group_category->setFieldsValues($fields_values);
		}
		
		return $group_category;
	}
}