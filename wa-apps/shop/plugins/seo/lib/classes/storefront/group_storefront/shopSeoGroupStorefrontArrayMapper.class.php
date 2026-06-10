<?php


class shopSeoGroupStorefrontArrayMapper
{
	private $settings_array_mapper;
	private $fields_values_array_mapper;
	
	public function __construct(
		shopSeoSettingsArrayMapper $settings_array_mapper,
		shopSeoFieldsValuesArrayMapper $field_value_array_mapper
	) {
		$this->settings_array_mapper = $settings_array_mapper;
		$this->fields_values_array_mapper = $field_value_array_mapper;
	}
	
	public function mapGroupStorefront(shopSeoGroupStorefront $group_storefront)
	{
		$result = array(
			'id' => $group_storefront->getId(),
			'name' => $group_storefront->getName(),
			'storefront_select_rule_type' => $group_storefront->getStorefrontSelectRuleType(),
			'storefront_select_rule_variants' => $group_storefront->getStorefrontSelectRuleVariants(),
			'sort' => $group_storefront->getSort(),
			'settings' => null,
			'fields_values' => null,
		);
		
		if ($group_storefront->getSettings())
		{
			$result['settings'] = $this->settings_array_mapper->mapSettings($group_storefront->getSettings());
		}
		
		if ($group_storefront->getFieldsValues())
		{
			$result['fields_values'] = $this->fields_values_array_mapper->mapFieldsValues($group_storefront->getFieldsValues());
		}
		
		return $result;
	}
	
	/**
	 * @param shopSeoGroupStorefront[] $groups_storefronts
	 * @return array
	 */
	public function mapGroupsStorefronts($groups_storefronts)
	{
		$result = array();
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$result[] = $this->mapGroupStorefront($group_storefront);
		}
		
		return $result;
	}
	
	/**
	 * @param $group_storefront_array
	 * @param shopSeoField[] $fields
	 * @return shopSeoGroupStorefront
	 */
	public function mapArray($group_storefront_array, $fields)
	{
		$group_storefront = new shopSeoGroupStorefront();
		$group_storefront->setId($group_storefront_array['id']);
		$group_storefront->setName($group_storefront_array['name']);
		$group_storefront->setStorefrontSelectRuleType($group_storefront_array['storefront_select_rule_type']);
		$group_storefront->setStorefrontSelectRuleVariants($group_storefront_array['storefront_select_rule_variants']);
		$group_storefront->setSort($group_storefront_array['sort']);
		
		if ($group_storefront_array['settings'])
		{
			$settings = new shopSeoStorefrontSettings();
			$settings->setGroupId($group_storefront->getId());
			$this->settings_array_mapper->mapArray($settings, $group_storefront_array['settings']);
			$group_storefront->setSettings($settings);
		}
		
		if ($group_storefront_array['fields_values'])
		{
			$fields_values = new shopSeoStorefrontFieldsValues();
			$fields_values->setGroupId($group_storefront->getId());
			$this->fields_values_array_mapper->mapArray($fields_values, $fields, $group_storefront_array['fields_values']);
			$group_storefront->setFieldsValues($fields_values);
		}
		
		return $group_storefront;
	}
}