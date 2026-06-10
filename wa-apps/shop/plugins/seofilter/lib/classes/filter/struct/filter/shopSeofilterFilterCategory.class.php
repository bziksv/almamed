<?php

/**
 * Class shopSeofilterFilterCategory
 * @property int $id
 * @property int $filter_id
 * @property int $category_id
 *
 * @method shopSeofilterFilterCategoryModel model()
 * @method shopSeofilterFilterCategory|null getById($id)
 *
 * relations
 * @property shopSeofilterFilter $filter
 * @property null|array $category
 */
class shopSeofilterFilterCategory extends shopSeofilterActiveRecord
{
	private $_category = false;

	/**
	 * shopSeofilterFilterCategory constructor.
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
			'filter' => array(self::BELONGS_TO, 'shopSeofilterFilter', 'filter_id'),
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
		return $this->_class . '_' . $this->filter_id . '_' . $this->category_id;
	}

	public function tryLoad($filter_id, $category_id)
	{
		$model = $this->model();
		$attributes = $model->getByField(array(
			'filter_id' => $filter_id,
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