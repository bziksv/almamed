<?php

/**
 * Class shopSeofilterFilterPersonalRuleStorefront
 * @property int $id
 * @property int $rule_id
 * @property string $storefront
 *
 * @method shopSeofilterFilterPersonalRuleStorefrontModel model()
 * @method shopSeofilterFilterPersonalRuleStorefront|null getById($id)
 *
 * relations
 * @property shopSeofilterFilterPersonalRule $rule
 */
class shopSeofilterFilterPersonalRuleStorefront extends shopSeofilterActiveRecord
{
	/**
	 * shopSeofilterFilterPersonalRuleStorefront constructor.
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
			'rule' => array(self::BELONGS_TO, 'shopSeofilterFilterPersonalRule', 'rule_id'),
		);
	}

	public function key()
	{
		return $this->_class . '_' . $this->rule_id . '_' . $this->storefront;
	}

	/**
	 * @param int $rule_id
	 * @param string $storefront_name
	 * @return bool
	 */
	public function tryLoad($rule_id, $storefront_name)
	{
		$model = $this->model();
		$attributes = $model->getByField(array(
			'rule_id' => $rule_id,
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