<?php

/**
 * Class shopSeofilterFilterPersonalCanonical
 *
 * @property int $id
 * @property int $filter_id
 * @property int $is_enabled
 * @property string $storefronts_use_mode
 * @property string $categories_use_mode
 * @property string $canonical_url_template
 *
 * @property int[] $category_ids
 * @property string[] $storefront_ids
 *
 * @method shopSeofilterFilterPersonalRuleModel model()
 * @method shopSeofilterFilterPersonalRule|null getById($id)
 *
 * relations
 * @property shopSeofilterFilterPersonalCanonicalCategory[] $categories
 * @property shopSeofilterFilterPersonalCanonicalStorefront[] $storefronts
 * @property shopSeofilterFilter $filter
 */
class shopSeofilterFilterPersonalCanonical extends shopSeofilterActiveRecord
{
	const DISABLED = '0';
	const ENABLED = '1';

	const USE_MODE_ALL = 'ALL';
	const USE_MODE_LISTED = 'LISTED';
	const USE_MODE_EXCEPT = 'EXCEPT';

	public function relations()
	{
		return array(
			'categories' => array(self::HAS_MANY, 'shopSeofilterFilterPersonalCanonicalCategory', 'canonical_id'),
			'storefronts' => array(self::HAS_MANY, 'shopSeofilterFilterPersonalCanonicalStorefront', 'canonical_id'),
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
	 * @return int[]
	 */
	public function getCategory_ids()
	{
		$ids = array();

		foreach ($this->categories as $category)
		{
			$ids[] = $category->category_id;
		}

		return $ids;
	}

	/**
	 * @param array $ids
	 */
	public function setCategory_ids($ids)
	{
		$objects = array();

		foreach ($ids as $id)
		{
			$objects[] = new shopSeofilterFilterPersonalCanonicalCategory($id);
		}

		$this->categories = $objects;
	}

	/**
	 * @return string[]
	 */
	public function getStorefront_ids()
	{
		$storefronts = array();

		foreach ($this->storefronts as $category)
		{
			$storefronts[] = $category->storefront;
		}

		return $storefronts;
	}

	/**
	 * @param string[] $storefronts
	 */
	public function setStorefront_ids($storefronts)
	{
		$objects = array();

		foreach ($storefronts as $storefront)
		{
			$objects[] = new shopSeofilterFilterPersonalCanonicalStorefront($storefront);
		}

		$this->storefronts = $objects;
	}

	protected function beforeSave()
	{
		if (is_bool($this->is_enabled))
		{
			$this->is_enabled = $this->is_enabled
				? self::ENABLED
				: self::DISABLED;
		}

		return true;
	}
}