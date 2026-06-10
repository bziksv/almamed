<?php


class shopSeoGroupStorefrontService
{
	private $group_source;
	private $group_storefront_source;
	private $storefront_settings_service;
	private $storefront_fields_values_service;
	private $category_settings_service;
	private $product_settings_service;
	private $category_field_value_service;
	private $product_field_value_service;
	
	private $groups_storefronts = array();
	
	public function __construct(
		shopSeoGroupStorefrontSource $group_source,
		shopSeoGroupStorefrontStorefrontSource $group_storefront_source,
		shopSeoStorefrontSettingsService $storefront_settings_service,
		shopSeoStorefrontFieldsValuesService $storefront_fields_values_service,
		shopSeoCategorySettingsService $category_settings_service,
		shopSeoProductSettingsService $product_settings_service,
		shopSeoCategoryFieldsValuesService $category_field_value_service,
		shopSeoProductFieldsValuesService $product_field_value_service
	) {
		$this->group_source = $group_source;
		$this->group_storefront_source = $group_storefront_source;
		$this->storefront_settings_service = $storefront_settings_service;
		$this->storefront_fields_values_service = $storefront_fields_values_service;
		$this->category_settings_service = $category_settings_service;
		$this->product_settings_service = $product_settings_service;
		$this->category_field_value_service = $category_field_value_service;
		$this->product_field_value_service = $product_field_value_service;
	}
	
	
	public function getById($id)
	{
		$row = $this->group_source->getByGroupId($id);
		
		if (is_null($row))
		{
			return null;
		}
		
		$group_storefront = $this->fromRow($row);
		return $group_storefront;
	}
	
	/**
	 * @return shopSeoGroupStorefront[]
	 */
	public function getAll()
	{
		$rows = $this->group_source->getAllGroups();
		$groups = array();
		
		foreach ($rows as $row)
		{
			$groups[] = $this->fromRow($row);
		}
		
		return $groups;
	}
	
	/**
	 * @param $storefront
	 * @return shopSeoGroupStorefront[]
	 */
	public function getByStorefront($storefront)
	{
		if (!isset($this->groups_storefronts[$storefront]))
		{
			$rows = $this->group_source->getByStorefront($storefront);
			$groups = array();
			
			foreach ($rows as $row)
			{
				$groups[] = $this->fromRow($row);
			}
			
			$this->groups_storefronts[$storefront] = $groups;
		}
		
		return $this->groups_storefronts[$storefront];
	}
	
	public function updateSort()
	{
		$this->group_source->updateSort();
	}
	
	public function store(shopSeoGroupStorefront $group_storefront)
	{
		$this->groups_storefronts = array();
		$row = $this->toRow($group_storefront);
		
		if (is_null($group_storefront->getId()))
		{
			$id = $this->group_source->addGroup($row);
			$group_storefront->setId($id);
		}
		else
		{
			$this->group_source->updateGroup($group_storefront->getId(), $row);
		}
		
		if ($group_storefront->getStorefrontSelectRuleVariants())
		{
			$rows = array();
			
			foreach ($group_storefront->getStorefrontSelectRuleVariants() as $variant)
			{
				$rows[] = array(
					'group_id' => $group_storefront->getId(),
					'storefront' => $variant,
				);
			}
			
			$this->group_storefront_source->updateByGroupId($group_storefront->getId(), $rows);
		}
		
		if ($group_storefront->getSettings())
		{
			$this->storefront_settings_service->store($group_storefront->getSettings());
		}
		
		if ($group_storefront->getFieldsValues())
		{
			$this->storefront_fields_values_service->store($group_storefront->getFieldsValues());
		}
	}
	
	public function delete(shopSeoGroupStorefront $group_storefront)
	{
		$this->groups_storefronts = array();
		
		if (!$group_storefront->getId())
		{
			return;
		}
		
		if (!$group_storefront->getSettings())
		{
			$this->loadSettings($group_storefront);
		}
		
		$this->group_storefront_source->deleteByGroupId($group_storefront->getId());
		$this->storefront_fields_values_service->deleteByGroupId($group_storefront->getId());
		$this->category_settings_service->deleteByGroupStorefrontId($group_storefront->getId());
		$this->product_settings_service->deleteByGroupStorefrontId($group_storefront->getId());
		$this->category_field_value_service->deleteByGroupStorefrontId($group_storefront->getId());
		$this->product_field_value_service->deleteByGroupStorefrontId($group_storefront->getId());
		$this->storefront_settings_service->delete($group_storefront->getSettings());
		
		$this->group_source->deleteGroup($group_storefront->getId());
		$group_storefront->setId(null);
	}
	
	public function loadRule(shopSeoGroupStorefront $group_storefront)
	{
		$rows = $this->group_storefront_source->getByGroupId($group_storefront->getId());
		$storefronts = array();
		
		foreach ($rows as $row)
		{
			$storefronts[] = $row['storefront'];
		}
		
		$group_storefront->setStorefrontSelectRuleVariants($storefronts);
	}
	
	public function loadSettings(shopSeoGroupStorefront $group_storefront)
	{
		$settings = $this->storefront_settings_service->getByGroupId($group_storefront->getId());
		$group_storefront->setSettings($settings);
	}
	
	/**
	 * @param shopSeoGroupStorefront $group_storefront
	 * @param shopSeoField[] $fields
	 */
	public function loadFieldsValues(shopSeoGroupStorefront $group_storefront, $fields)
	{
		$fields_values = $this->storefront_fields_values_service->getByGroupIdAndFields(
			$group_storefront->getId(),
			$fields
		);
		$group_storefront->setFieldsValues($fields_values);
	}
	
	private function toRow(shopSeoGroupStorefront $group_storefront)
	{
		return array(
			'id' => $group_storefront->getId(),
			'name' => $group_storefront->getName(),
			'storefront_select_rule_type' => $group_storefront->getStorefrontSelectRuleType(),
			'storefront_select_rule_variants' => $group_storefront->getStorefrontSelectRuleVariants(),
			'sort' => $group_storefront->getSort(),
		);
	}
	
	private function fromRow($row)
	{
		$group_storefront = new shopSeoGroupStorefront();
		$group_storefront->setId((int) $row['id']);
		$group_storefront->setName($row['name']);
		$group_storefront->setStorefrontSelectRuleType($row['storefront_select_rule_type']);
		$group_storefront->setSort((int) $row['sort']);
		
		return $group_storefront;
	}
}