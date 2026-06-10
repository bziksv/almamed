<?php

class shopSeofilterTemplateCollector
{
	private $storefront;
	private $category_id;
	private $category_page;
	private $active_contexts;
	/** @var shopSeofilterLayoutsCollection */
	private $collection;
	/** @var shopSeofilterFilter */
	private $filter;
	/** @var shopSeofilterDefaultTemplateModel */
	private $default_template_model;


	public function __construct(shopSeofilterFilter $filter, $storefront, $category_id, $category_page)
	{
		$this->collection = new shopSeofilterLayoutsCollection();

		$this->filter = $filter;
		$this->storefront = $storefront;
		$this->category_id = $category_id;
		$this->category_page = $category_page;
		$this->default_template_model = new shopSeofilterDefaultTemplateModel();
		$this->active_contexts = $this->getActiveContexts();

		$this->collectPersonalRulesLayouts();
		$this->collectDefaultLayouts();
		$this->collectGeneralDefaultLayouts();
	}

	public function getTemplates()
	{
		return $this->collection->getUpperItems();
	}

	private function collectPersonalRulesLayouts()
	{
		$rule = $this->filter->getActivePersonalRule($this->storefront, $this->category_id);

		if ($rule)
		{
			$rule_templates = $rule->templates($this->active_contexts);
			foreach ($rule_templates as $context => $template)
			{
				$this->collection->push($template);
			}
		}
	}

	private function collectDefaultLayouts()
	{
		$default_templates = $this->default_template_model->getActiveTemplates($this->storefront, $this->active_contexts);
		foreach ($default_templates as $template)
		{
			$this->collection->push($template, shopSeofilterLayoutsCollection::ONLY_EMPTY_BASIC_PRIORITY);
		}
	}

	private function collectGeneralDefaultLayouts()
	{
		$general_default_templates = $this->default_template_model->getActiveTemplates('*', $this->active_contexts);
		foreach ($general_default_templates as $template)
		{
			$this->collection->push($template, shopSeofilterLayoutsCollection::ONLY_EMPTY_BASIC_PRIORITY);
		}
	}

	private function getActiveContexts()
	{
		$active_contexts = array(
			shopSeofilterDefaultTemplateModel::CONTEXT_DEFAULT,
		);

		if (wa_is_int($this->category_page) && $this->category_page > 1)
		{
			$active_contexts[] = shopSeofilterDefaultTemplateModel::CONTEXT_PAGINATION;
		}

		return $active_contexts;
	}
}