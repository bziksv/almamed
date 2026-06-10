<?php


class shopSeoGroupCategory
{
	private $id;
	private $name;
	private $storefront_select_rule_type;
	private $storefront_select_rule_variants;
	private $category_select_rule_type;
	private $category_select_rule_variants;
	private $sort;
	/** @var shopSeoGroupCategorySettings */
	private $settings;
	/** @var shopSeoGroupCategoryFieldsValues */
	private $fields_values;
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
		
		if ($this->getSettings())
		{
			$this->getSettings()->setGroupId($id);
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getStorefrontSelectRuleType()
	{
		return $this->storefront_select_rule_type;
	}
	
	public function setStorefrontSelectRuleType($storefront_select_rule_type)
	{
		$this->storefront_select_rule_type = $storefront_select_rule_type;
	}
	
	public function getStorefrontSelectRuleVariants()
	{
		return $this->storefront_select_rule_variants;
	}
	
	public function setStorefrontSelectRuleVariants($storefront_select_rule_variants)
	{
		$this->storefront_select_rule_variants = $storefront_select_rule_variants;
	}
	
	public function getCategorySelectRuleType()
	{
		return $this->category_select_rule_type;
	}
	
	public function setCategorySelectRuleType($category_select_rule_type)
	{
		$this->category_select_rule_type = $category_select_rule_type;
	}
	
	public function getCategorySelectRuleVariants()
	{
		return $this->category_select_rule_variants;
	}
	
	public function setCategorySelectRuleVariants($category_select_rule_variants)
	{
		$this->category_select_rule_variants = $category_select_rule_variants;
	}
	
	public function getSort()
	{
		return $this->sort;
	}
	
	public function setSort($sort)
	{
		$this->sort = $sort;
	}
	
	public function getSettings()
	{
		return $this->settings;
	}
	
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}
	
	public function getFieldsValues()
	{
		return $this->fields_values;
	}
	
	public function setFieldsValues($fields_values)
	{
		$this->fields_values = $fields_values;
	}
}