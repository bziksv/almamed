<?php


class shopSeoGroupCategoryService
{
	private $group_source;
	private $group_storefront_source;
	private $group_category_source;
	private $group_settings_service;
	private $fields_values_service;
	
	private $groups = array();
	
	public function __construct(
		shopSeoGroupCategorySource $group_source,
		shopSeoGroupCategoryStorefrontSource $group_storefront_source,
		shopSeoGroupCategoryCategorySource $group_category_source,
		shopSeoGroupCategorySettingsService $group_settings_service,
		shopSeoGroupCategoryFieldsValuesService $fields_values_service
	) {
		$this->group_source = $group_source;
		$this->group_storefront_source = $group_storefront_source;
		$this->group_category_source = $group_category_source;
		$this->group_settings_service = $group_settings_service;
		$this->fields_values_service = $fields_values_service;
	}
	
	
	public function getById($id)
	{
		$row = $this->group_source->getByGroupId($id);
		
		if (is_null($row))
		{
			return null;
		}
		
		$group_category = $this->fromRow($row);
		
		return $group_category;
	}
	
	/**
	 * @param $storefront
	 * @param $category_id
	 * @return shopSeoGroupCategory[]
	 */
	public function getByStorefrontAndCategoryId($storefront, $category_id)
	{
		$key = json_encode(array('storefront' => $storefront, 'category_id' => $category_id));
		
		if (!isset($this->groups[$key]))
		{
			$rows = $this->group_source->getByStorefrontAndCategoryId($storefront, $category_id);
			$groups = array();
			
			foreach ($rows as $row)
			{
				$groups[] = $this->fromRow($row);
			}
			
			$this->groups[$key] = $groups;
		}
		
		return $this->groups[$key];
	}
	
	/**
	 * @return shopSeoGroupCategory[]
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
	
	public function updateSort()
	{
		$this->group_source->updateSort();
	}
	
	public function store(shopSeoGroupCategory $group_category)
	{
		$this->groups = array();
		
		$row = $this->toRow($group_category);
		
		if (is_null($group_category->getId()))
		{
			$id = $this->group_source->addGroup($row);
			$group_category->setId($id);
		}
		else
		{
			$this->group_source->updateGroup($group_category->getId(), $row);
		}
		
		if ($group_category->getStorefrontSelectRuleVariants())
		{
			$rows = array();
			
			foreach ($group_category->getStorefrontSelectRuleVariants() as $variant)
			{
				$rows[] = array(
					'group_id' => $group_category->getId(),
					'storefront' => $variant,
				);
			}
			
			$this->group_storefront_source->updateByGroupId($group_category->getId(), $rows);
		}
		
		if ($group_category->getCategorySelectRuleVariants())
		{
			$rows = array();
			
			foreach ($group_category->getCategorySelectRuleVariants() as $variant)
			{
				$rows[] = array(
					'group_id' => $group_category->getId(),
					'category_id' => $variant,
				);
			}
			
			$this->group_category_source->updateByGroupId($group_category->getId(), $rows);
		}
		
		if ($group_category->getSettings())
		{
			$this->group_settings_service->store($group_category->getSettings());
		}
		
		if ($group_category->getFieldsValues())
		{
			$this->fields_values_service->store($group_category->getFieldsValues());
		}
	}
	
	public function delete(shopSeoGroupCategory $group_category)
	{
		$this->groups = array();
		
		if (!$group_category->getId())
		{
			return;
		}
		
		if (!$group_category->getSettings())
		{
			$this->loadSettings($group_category);
		}
		
		$this->group_settings_service->delete($group_category->getSettings());
		$this->fields_values_service->deleteByGroupId($group_category->getId());
		
		$this->group_source->deleteGroup($group_category->getId());
		$group_category->setId(null);
	}
	
	public function loadSettings(shopSeoGroupCategory $group_category)
	{
		$settings = $this->group_settings_service->getByGroupId($group_category->getId());
		$group_category->setSettings($settings);
	}
	
	/**
	 * @param shopSeoGroupCategory $group_category
	 * @param shopSeoField[] $fields
	 */
	public function loadFieldsValues(shopSeoGroupCategory $group_category, $fields)
	{
		$fields_values = $this->fields_values_service->getByGroupIdAndFields(
			$group_category->getId(),
			$fields
		);
		$group_category->setFieldsValues($fields_values);
	}
	
	public function loadRules(shopSeoGroupCategory $group_category)
	{
		$rows = $this->group_storefront_source->getByGroupId($group_category->getId());
		$storefronts = array();
		
		foreach ($rows as $row)
		{
			$storefronts[] = $row['storefront'];
		}
		
		$group_category->setStorefrontSelectRuleVariants($storefronts);
		
		$rows = $this->group_category_source->getByGroupId($group_category->getId());
		$categories = array();
		
		foreach ($rows as $row)
		{
			$categories[] = $row['category_id'];
		}
		
		$group_category->setCategorySelectRuleVariants($categories);
	}
	
	private function toRow(shopSeoGroupCategory $group_category)
	{
		return array(
			'id' => $group_category->getId(),
			'name' => $group_category->getName(),
			'storefront_select_rule_type' => $group_category->getStorefrontSelectRuleType(),
			'category_select_rule_type' => $group_category->getCategorySelectRuleType(),
			'sort' => $group_category->getSort(),
		);
	}
	
	private function fromRow($row)
	{
		$group_storefront = new shopSeoGroupCategory();
		$group_storefront->setId((int) $row['id']);
		$group_storefront->setName($row['name']);
		$group_storefront->setStorefrontSelectRuleType($row['storefront_select_rule_type']);
		$group_storefront->setCategorySelectRuleType($row['category_select_rule_type']);
		$group_storefront->setSort((int) $row['sort']);
		
		return $group_storefront;
	}
}