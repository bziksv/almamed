<?php


class shopSeoGroupStorefront
{
	private $id;
	private $name;
	private $storefront_select_rule_type;
	private $storefront_select_rule_variants;
	private $sort;
	/** @var shopSeoStorefrontSettings */
	private $settings;
	/** @var shopSeoStorefrontFieldsValues */
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
			$this->getSettings()->setGroupId($this->getId());
		}
		
		if ($this->getFieldsValues())
		{
			$this->getFieldsValues()->setGroupId($this->getId());
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
	
	public function setSettings(shopSeoStorefrontSettings $settings)
	{
		$settings->setGroupId($this->getId());
		
		$this->settings = $settings;
	}
	
	public function getFieldsValues()
	{
		return $this->fields_values;
	}
	
	/**
	 * @param shopSeoStorefrontFieldsValues $fields_values
	 */
	public function setFieldsValues($fields_values)
	{
		$fields_values->setGroupId($this->getId());
		
		$this->fields_values = $fields_values;
	}
}