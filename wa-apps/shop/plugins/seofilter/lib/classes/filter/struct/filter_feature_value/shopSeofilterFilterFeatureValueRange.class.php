<?php

/**
 * Class shopSeofilterFilterFeatureValueRange
 * @property int $id
 * @property int $filter_id
 * @property int $feature_id
 * @property string $type
 * @property float $begin_base_unit
 * @property float $end_base_unit
 * @property string $unit
 * @property float $begin
 * @property float $end
 * @property int $sort
 *
 * @method shopSeofilterFilterFeatureValueRangeModel model()
 * @method shopSeofilterFilterFeatureValueRange|null getById($id)
 *
 * relations
 * @property shopSeofilterFilter $filter
 */
class shopSeofilterFilterFeatureValueRange extends shopSeofilterFilterFeatureValueActiveRecord
{
	const ERROR_KEY_PREFIX = 'feature_value_range';

	private static $units = null;
	private static $currencies = null;

	public function __construct($attributes = null)
	{
		parent::__construct($attributes);

		if (self::$units === null)
		{
			$dimension = shopDimension::getInstance();
			self::$units = $dimension->getList();

			$currency_model = new shopCurrencyModel();
			self::$currencies = $currency_model->getCurrencies();
		}
	}

	public function relations()
	{
		return array(
			'filter' => array(self::BELONGS_TO, 'shopSeofilterFilter', 'filter_id'),
		);
	}

	public function getRangeWithUnits()
	{
		$dimension = shopDimension::getInstance();

		$type = $this->type;
		$begin_base = $this->begin_base_unit;
		$end_base = $this->end_base_unit;

		$range = array();

		$range['end'] = $range['beg'] = array();

		$range['beg']['base_value'] = $begin_base;
		$range['end']['base_value'] = $end_base;

		$base_unit = shopDimension::getBaseUnit($type);
		if (!isset($base_unit['value']))
		{
			return $range;
		}

		foreach (shopDimension::getUnits($type) as $unit => $arr)
		{
			$range['beg'][$unit] = $dimension->convert($begin_base, $type, $unit);
			$range['end'][$unit] = $dimension->convert($end_base, $type, $unit);
		}

		return $range;
	}

	public function setRange($begin, $end, $unit)
	{
		$dimension = shopDimension::getInstance();

		$type = $this->type;

		$this->begin = $begin;
		$this->end = $end;
		$this->unit = $unit;

		$base_unit = shopDimension::getBaseUnit($type);

		if (!$unit || $this->isPrice())
		{
			$this->begin_base_unit = $begin;
			$this->end_base_unit = $end;
		}
		else
		{
			$this->begin_base_unit = $begin === null
				? null
				: $dimension->convert($begin, $type, $base_unit['value'], $unit);
			$this->end_base_unit = $end === null
				? null
				: $dimension->convert($end, $type, $base_unit['value'], $unit);
		}
	}

	public function setAttributes($attributes)
	{
		parent::setAttributes($attributes);

		if ($this->isPrice() && !wa_is_int($this->feature_id))
		{
			$this->unit = substr($this->feature_id, 6, 3);
			$this->feature_id = 0;
		}

		if (strlen($this->begin) === 0)
		{
			$this->begin = null;
		}
		if (strlen($this->end) === 0)
		{
			$this->end = null;
		}

		$this->setRange($this->begin, $this->end, $this->unit);
	}

	public function isPrice()
	{
		return $this->type === shopSeofilterFilter::TYPE_PRICE;
	}

	public function deleteForCurrencies($code)
	{
		$this->model()->deleteByField(array(
			'type' => shopSeofilterFilter::TYPE_PRICE,
			'unit' => $code,
		));
	}

	public function key()
	{
		$feature_id = $this->isPrice()
			? shopSeofilterFilter::TYPE_PRICE . '_' . $this->unit
			: $this->feature_id;

		return self::ERROR_KEY_PREFIX . '_' . $feature_id . '_' . $this->type . '_' . ($this->begin === null ? '' : $this->begin) . '_' . ($this->end === null ? '' : $this->end);
	}

	public function validate()
	{
		$is_valid = parent::validate();

		if ($is_valid)
		{
			if (
				$this->begin !== null
				&& $this->end !== null
				&& ($this->begin - $this->end) > 1e-6 // $this->begin > $this->end
			)
			{
				$is_valid = false;
				$this->_errors['begin'] = 'Значение "От" должно быть меньше "До"';
			}
			elseif ($this->begin === null && $this->end === null)
			{
				$is_valid = false;
				$this->_errors['begin'] = 'Укажите хотя бы один из концов диапазона';
				$this->_errors['end'] = 'Укажите хотя бы один из концов диапазона';
			}
		}

		return $is_valid;
	}

	public function getValueName()
	{
		$strings = array();

		if ($this->begin !== null)
		{
			$strings[] = 'от ' . $this->begin;
		}

		if ($this->end !== null)
		{
			$strings[] = 'до ' . $this->end;
		}

		return count($strings) != 0
			? implode(' ', $strings) . ' ' . $this->unitLocale()
			: '';
	}

	private function unitLocale()
	{
		$type = $this->type;
		$unit = $this->unit;

		if ($this->isPrice() && isset(self::$currencies[$unit]))
		{
			return self::$currencies[$unit]['sign'];
		}

		return isset(self::$units[$type]) && isset(self::$units[$type]['units'][$unit])
			? self::$units[$type]['units'][$unit]['name']
			: '';
	}
}