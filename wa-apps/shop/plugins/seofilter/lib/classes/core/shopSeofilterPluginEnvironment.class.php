<?php

class shopSeofilterPluginEnvironment
{
	private static $instance = null;

	/** @var shopSeofilterContext|null */
	private $context = null;
	private $current_filter_params_by_feature_id = false;
	private $current_feature_value_ids = false;
	private $current_feature_value_ids_grouped_by_code = false;

	/** @var shopSeofilterBlockedFeatureValues|null */
	private $blocking = null;

	/**
	 * @return shopSeofilterPluginEnvironment
	 */
	public static function instance()
	{
		if (self::$instance === null)
		{
			self::$instance = new shopSeofilterPluginEnvironment();
		}

		return self::$instance;
	}

	private function __construct()
	{
	}

	public function getCurrentFilterParamsByFeatureId()
	{
		return $this->current_filter_params_by_feature_id;
	}

	public function getCurrentFeatureValueIds()
	{
		return $this->current_feature_value_ids;
	}

	public function getCurrentFeatureValueIdsGroupedByCode()
	{
		return $this->current_feature_value_ids_grouped_by_code;
	}

	public function setFilterParamsByFeatureId($filter_params_by_feature_id)
	{
		if ($this->current_filter_params_by_feature_id === false)
		{
			$this->current_filter_params_by_feature_id = $filter_params_by_feature_id;
		}
	}

	public function setFeatureValueIds($current_feature_value_ids)
	{
		if ($this->current_feature_value_ids === false)
		{
			$this->current_feature_value_ids = $current_feature_value_ids;
			$this->current_feature_value_ids_grouped_by_code = array();

			if (is_array($current_feature_value_ids) && count($current_feature_value_ids))
			{
				$features = shopSeofilterFilterFeatureValuesHelper::getFeatures('id', array_keys($current_feature_value_ids), 'id');

				foreach ($current_feature_value_ids as $feature_id => $values)
				{
					if (!array_key_exists($feature_id, $features))
					{
						continue;
					}

					$this->current_feature_value_ids_grouped_by_code[$features[$feature_id]->code] = $values;
				}
			}
		}
	}

	public function getCurrentFeatureValueIdsForBlocking(shopSeofilterRouting $plugin_routing)
	{
		if ($this->blocking === null)
		{
			$category = $plugin_routing->getCategory();
			$params = shopSeofilterFilterFeatureValuesHelper::normalizeParams(waRequest::get());

			$filters = shopSeofilterHelper::getViewFilters($category['id']);

			if (!is_array($filters) || !count($params))
			{
				return false;
			}

			$this->blocking = new shopSeofilterBlockedFeatureValues($filters, $params, $category);
		}

		return $this->blocking->getAvailableFeatureValueIds();
	}

	private function __clone()
	{
		throw new waException("can't clone instance of [shopSeofilterPluginEnvironment]");
	}

	public function setContext(shopSeofilterContext $context)
	{
		if ($this->context === null)
		{
			$this->context = $context;
		}
	}

	public function getContext()
	{
		return $this->context;
	}
}
