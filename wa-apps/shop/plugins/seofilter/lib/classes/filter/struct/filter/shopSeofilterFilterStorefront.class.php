<?php

/**
 * Class shopSeofilterFilterStorefront
 * @property int $id
 * @property int $filter_id
 * @property string $storefront
 *
 * @method shopSeofilterFilterStorefrontModel model()
 * @method shopSeofilterFilterStorefront|null getById($id)
 *
 * relations
 * @property shopSeofilterFilter $filter
 */
class shopSeofilterFilterStorefront extends shopSeofilterActiveRecord
{
	/**
	 * shopSeofilterFilterStorefront constructor.
	 * @param null|array|string $attributes
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
			$this->storefront = $attributes;
		}
	}

	public function relations()
	{
		return array(
			'filter' => array(self::BELONGS_TO, 'shopSeofilterFilter', 'filter_id'),
		);
	}

	public function key()
	{
		return $this->_class . '_' . $this->filter_id . '_' . $this->storefront;
	}

	/**
	 * @param int $filter_id
	 * @param string $storefront_name
	 * @return bool
	 */
	public function tryLoad($filter_id, $storefront_name)
	{
		$model = $this->model();
		$attributes = $model->getByField(array(
			'filter_id' => $filter_id,
			'storefront' => $storefront_name,
		), false);

		if (!$attributes)
		{
			return false;
		}

		$this->setAttributes($attributes);

		return true;
	}
}