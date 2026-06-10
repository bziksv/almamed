<?php

/**
 * Class shopSeofilterFilterPersonalRule
 * @property int $id
 * @property int $filter_id
 * @property int $is_enabled
 * @property string $default_product_sort
 * @property string $seo_h1
 * @property string $seo_description
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $additional_description
 * @property string $storefronts_use_mode
 * @property string $categories_use_mode
 * @property array $rule_storefronts
 * @property array $rule_categories
 *
 * @property int $is_pagination_templates_enabled
 * @property string $seo_h1_pagination
 * @property string $seo_description_pagination
 * @property string $meta_title_pagination
 * @property string $meta_description_pagination
 * @property string $meta_keywords_pagination
 * @property string $additional_description_pagination
 *
 * @method shopSeofilterFilterPersonalRuleModel model()
 * @method shopSeofilterFilterPersonalRule|null getById($id)
 *
 * @property shopSeofilterFilter $filter
 *
 * relations
 * @property shopSeofilterFilterPersonalRuleCategory[] $categories
 * @property shopSeofilterFilterPersonalRuleStorefront[] $storefronts
 */
class shopSeofilterFilterPersonalRule extends shopSeofilterActiveRecord
{
	const DISABLED = 0;
	const ENABLED = 1;

	const DEFAULT_SORT = 'filter_id';
	const DEFAULT_ORDER = 'asc';

	const USE_MODE_ALL = 'ALL';
	const USE_MODE_LISTED = 'LISTED';
	const USE_MODE_EXCEPT = 'EXCEPT';

	public function relations()
	{
		return array(
			'categories' => array(self::HAS_MANY, 'shopSeofilterFilterPersonalRuleCategory', 'rule_id'),
			'storefronts' => array(self::HAS_MANY, 'shopSeofilterFilterPersonalRuleStorefront', 'rule_id'),
			'filter' => array(self::BELONGS_TO, 'shopSeofilterFilter', 'filter_id'),
		);
	}

	public function validate()
	{
		$valid = parent::validate();

		if ($this->categories_use_mode != self::USE_MODE_ALL)
		{
			if (isset($this->_save_relations['categories']) && count($this->_save_relations['categories']) == 0)
			{
				$valid = false;
				$this->_errors['categories'] = 'Выберите категории';
			}
		}
		if ($this->storefronts_use_mode != self::USE_MODE_ALL)
		{
			if (isset($this->_save_relations['storefronts']) && count($this->_save_relations['storefronts']) == 0)
			{
				$valid = false;
				$this->_errors['storefronts'] = 'Выберите витрины';
			}
		}

		return $valid;
	}

	/**
	 * @return array
	 */
	public function getRule_categories()
	{
		$ids = array();

		foreach ($this->categories as $category)
		{
			$ids[] = $category->category_id;
		}

		return $ids;
	}

	/**
	 * @param array $categories
	 */
	public function setRule_categories($categories)
	{
		$objects = array();

		foreach ($categories as $id)
		{
			$objects[] = new shopSeofilterFilterPersonalRuleCategory($id);
		}

		$this->categories = $objects;
	}

	/**
	 * @return array
	 */
	public function getRule_storefronts()
	{
		$storefronts = array();

		foreach ($this->storefronts as $category)
		{
			$storefronts[] = $category->storefront;
		}

		return $storefronts;
	}

	/**
	 * @param array $storefronts
	 */
	public function setRule_storefronts($storefronts)
	{
		$objects = array();

		foreach ($storefronts as $storefront)
		{
			$objects[] = new shopSeofilterFilterPersonalRuleStorefront($storefront);
		}

		$this->storefronts = $objects;
	}

	public function enableById($ids)
	{
		return $this->updateFieldById($ids, 'is_enabled', shopSeofilterFilterPersonalRule::ENABLED);
	}

	public function disableById($ids)
	{
		return $this->updateFieldById($ids, 'is_enabled', shopSeofilterFilterPersonalRule::DISABLED);
	}

	/**
	 * @param array|null $contexts
	 * @return array
	 */
	public function templates($contexts = null)
	{
		$templates = array();

		if ($contexts === null || in_array(shopSeofilterDefaultTemplateModel::CONTEXT_DEFAULT, $contexts))
		{
			$templates[shopSeofilterDefaultTemplateModel::CONTEXT_DEFAULT] = array(
				'meta_title' => $this->meta_title,
				'meta_description' => $this->meta_description,
				'meta_keywords' => $this->meta_keywords,
				'h1' => $this->seo_h1,
				'description' => $this->seo_description,
				'additional_description' => $this->additional_description,
			);
		}

		if ($this->is_pagination_templates_enabled == '1' && ($contexts === null || in_array(shopSeofilterDefaultTemplateModel::CONTEXT_PAGINATION, $contexts)))
		{
			$templates[shopSeofilterDefaultTemplateModel::CONTEXT_PAGINATION] = array(
				'meta_title' => $this->meta_title_pagination,
				'meta_description' => $this->meta_description_pagination,
				'meta_keywords' => $this->meta_keywords_pagination,
				'h1' => $this->seo_h1_pagination,
				'description' => $this->seo_description_pagination,
				'additional_description' => $this->additional_description_pagination,
			);
		}

		uksort($templates, array($this, '_compareContextOrder'));
		return $templates;
	}

	public function getDefaultSortSort()
	{
		if (!$this->default_product_sort)
		{
			return '';
		}

		$tmp = explode(' ', $this->default_product_sort);
		return count($tmp) > 0 ? $tmp[0] : '';
	}

	public function getDefaultSortOrder()
	{
		if (!$this->default_product_sort)
		{
			return '';
		}

		$tmp = explode(' ', $this->default_product_sort);
		return count($tmp) == 2 ? $tmp[1] : '';
	}

	protected function beforeSave()
	{
		if (is_bool($this->is_enabled))
		{
			$this->is_enabled = $this->is_enabled
				? self::ENABLED
				: self::DISABLED;
		}

		if (is_bool($this->is_pagination_templates_enabled))
		{
			$this->is_pagination_templates_enabled = $this->is_pagination_templates_enabled
				? self::ENABLED
				: self::DISABLED;
		}

		return true;
	}

	private function _compareContextOrder($context_1, $context_2)
	{
		if ($context_1 === $context_2)
		{
			return 0;
		}

		return $context_1 == shopSeofilterDefaultTemplateModel::CONTEXT_PAGINATION
			? -1
			: 1;
	}
}