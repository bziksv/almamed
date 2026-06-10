<?php

/**
 * Class shopSeofilterFilterPersonalCanonicalStorefront
 *
 * @property int $id
 * @property int $canonical_id
 * @property string $storefront
 */
class shopSeofilterFilterPersonalCanonicalStorefront extends shopSeofilterActiveRecord
{
	/**
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
			'canonical' => array(self::BELONGS_TO, 'shopSeofilterFilterPersonalCanonical', 'canonical_id'),
		);
	}

	public function key()
	{
		return $this->_class . '_' . $this->canonical_id . '_' . $this->storefront;
	}

	public function tryLoad($canonical_id, $storefront)
	{
		$model = $this->model();
		$attributes = $model->getByField(array(
			'canonical_id' => $canonical_id,
			'storefront' => $storefront,
		), false);

		if (!$attributes)
		{
			return false;
		}

		$this->setAttributes($attributes);

		return true;
	}
}