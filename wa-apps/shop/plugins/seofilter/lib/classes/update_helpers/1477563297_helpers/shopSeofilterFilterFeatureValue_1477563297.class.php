<?php

/**
 * Class shopSeofilterFilterFeatureValue_1477563297
 *
 * @property $feature_id
 * @property $value_id
 * @property $sort
 */
class shopSeofilterFilterFeatureValue_1477563297 extends shopSeofilterAR_1477563297
{
	public static $feature_codes = array();

	public function hash()
	{
		$feature_id = $this->attributes['feature_id'];
		if (!array_key_exists($feature_id, self::$feature_codes))
		{
			$feature_model = new shopFeatureModel();
			self::$feature_codes[$feature_id] = $feature_model->getById($feature_id);
		}

		if (!self::$feature_codes[$feature_id])
		{
			return '';
		}

		$params = array(
			self::$feature_codes[$feature_id]['code'] => array($this->attributes['value_id'])
		);

		return sha1(http_build_query($params, null, '&'));
	}
}