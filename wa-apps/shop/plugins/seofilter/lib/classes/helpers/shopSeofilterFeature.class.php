<?php

class shopSeofilterFeature implements ArrayAccess
{
	const PRICE_FEATURE_ID = 'price';
	const PRICE_FEATURE_CODE = 'price';
	const PRICE_FEATURE_TYPE = 'price';

	private static $price_feature_instance = null;

	public $id = null;
	public $parent_id;
	public $code;
	public $status;
	public $name;
	public $type;
	public $selectable;
	public $multiple;
	public $count;

	public $values = array();

	public function __construct($feature)
	{
		$this->id = $feature['id'];
		$this->parent_id = $feature['parent_id'];
		$this->code = $feature['code'];
		$this->status = $feature['status'];
		$this->name = $feature['name'];
		$this->type = $feature['type'];
		$this->selectable = $feature['selectable'];
		$this->multiple = $feature['multiple'];
		$this->count = $feature['count'];
	}

	public function isExists()
	{
		return $this->id !== null;
	}

	public function isPrice()
	{
		return $this->code === self::PRICE_FEATURE_CODE;
	}

	/**
	 * // todo создать отдельный класс
	 * @return shopSeofilterFeature
	 */
	public static function getPriceFeature()
	{
		if (self::$price_feature_instance === null)
		{
			self::$price_feature_instance = new shopSeofilterFeature(array(
				'id' => self::PRICE_FEATURE_ID,
				'parent_id' => null,
				'code' => self::PRICE_FEATURE_CODE,
				'status' => shopFeatureModel::STATUS_PUBLIC,
				'name' => 'Цена',
				'type' => self::PRICE_FEATURE_TYPE,
				'selectable' => 0,
				'multiple' => 0,
				'count' => 0,
			));
		}

		return self::$price_feature_instance;
	}

	public function offsetExists($offset)
	{
		return $offset === 'id'
			|| $offset === 'parent_id'
			|| $offset === 'code'
			|| $offset === 'status'
			|| $offset === 'name'
			|| $offset === 'type'
			|| $offset === 'selectable'
			|| $offset === 'multiple'
			|| $offset === 'count'
			|| $offset === 'values';
	}

	public function offsetGet($offset)
	{
		return $this->$offset;
	}

	public function offsetSet($offset, $value)
	{
		$this->$offset = $value;
	}

	public function offsetUnset($offset)
	{
		$this->$offset = null;
	}

	public function assoc()
	{
		return array(
			'id' => $this->id ,
			'parent_id' => $this->parent_id,
			'code' => $this->code,
			'status' => $this->status,
			'name' => $this->name,
			'type' => $this->type,
			'selectable' => $this->selectable,
			'multiple' => $this->multiple,
			'count' => $this->count,
		);
	}
}