<?php

/**
 * Class shopSeofilterFilterPersonalRuleCategory
 * @property int $id
 * @property int $rule_id
 * @property int $category_id
 *
 * @method shopSeofilterFilterPersonalRuleCategoryModel model()
 * @method shopSeofilterFilterPersonalRuleCategory|null getById($id)
 *
 * relations
 * @property shopSeofilterFilterPersonalRule $rule
 * @property null|array $category
 */
class shopSeofilterFilterPersonalRuleCategory extends shopSeofilterActiveRecord
{
	private $_category = false;

	/**
	 * shopSeofilterFilterPersonalRuleCategory constructor.
	 * @param null|array|int $attributes
	 */
	public function __construct($attributes = null)
	{
		if (is_array($attributes))
		{
			parent::__construct($attributes);
		}
		else
		{
			parent::__construct();
			$this->category_id = $attributes;
		}
	}

	public function relations()
	{
		return array(
			'rule' => array(self::BELONGS_TO, 'shopSeofilterFilterPersonalRule', 'rule_id'),
		);
	}

	public function getCategory()
	{
		if ($this->_category === false)
		{
			$model = new shopCategoryModel();
			$this->_category = $model->getById($this->category_id);
		}

		return $this->_category;
	}

	public function key()
	{
		return $this->_class . '_' . $this->rule_id . '_' . $this->category_id;
	}

	public function tryLoad($rule_id, $category_id)
	{
		$model = $this->model();
		$attributes = $model->getByField(array(
			'rule_id' => $rule_id,
			'category_id' => $category_id,
		), false);

		if (!$attributes)
		{
			return false;
		}

		$this->setAttributes($attributes);

		return true;
	}
}