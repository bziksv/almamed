<?php

/**
 * Class shopSeofilterFilterPersonalCanonicalCategory
 *
 * @property int $id
 * @property int $canonical_id
 * @property int $category_id
 */
class shopSeofilterFilterPersonalCanonicalCategory extends shopSeofilterActiveRecord
{
	/**
	 * @param null|array|int $attributes
	 */
	public function __construct($attributes = null)
	{
		if (is_array($attributes))
		{
			parent::__construct($attributes);
		}
		elseif (wa_is_int($attributes))
		{
			parent::__construct();
			$this->category_id = $attributes;
		}
		else
		{
			parent::__construct($attributes);
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
		return $this->_class . '_' . $this->canonical_id . '_' . $this->category_id;
	}

	public function tryLoad($canonical_id, $category_id)
	{
		$model = $this->model();
		$attributes = $model->getByField(array(
			'canonical_id' => $canonical_id,
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