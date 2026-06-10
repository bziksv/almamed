<?php

class shopSeofilterReplaceSet extends shopSeofilterReplacesSet
{
	const TYPE_ARRAY = 'TYPE_ARRAY';
	const TYPE_SCALAR = 'TYPE_SCALAR';

	private $storefront_fields = array();

	public function setStorefrontFields($storefront_fields)
	{
		$this->storefront_fields = $storefront_fields;
	}

	/**
	 * @return shopSeofilterIReplacer[]
	 */
	public function getReplaces()
	{
		/** @var array $vars */
		$vars = $this->view->getVars();

		$replaces = array();

		foreach ($this->seofilterVariablesParams() as $variable_params)
		{
			list($var_name, $var_keys) = $variable_params;

			$var_key = array_shift($var_keys);
			$var_value = '';

			if (array_key_exists($var_key, $vars))
			{
				$var_value = $vars[$var_key];

				foreach ($var_keys as $var_key)
				{
					if (!is_array($var_value) || !array_key_exists($var_key, $var_value))
					{
						break;
					}
					$var_value = $var_value[$var_key];
				}
			}

			try
			{
				$replaces[] = new shopSeofilterVariable($var_name, $var_value);
			}
			catch (Exception $e)
			{
			}
		}

		try
		{
			$replaces[] = new shopSeofilterArrayVariable('parent_categories', $vars['parent_categories_names']);
		}
		catch (Exception $e)
		{
		}

		foreach ($this->storefront_fields as $id => $field)
		{
			try
			{
				$replaces[] = new shopSeofilterVariable('storefront_field_' . $id, $field['value']);
			}
			catch (Exception $e)
			{
			}
		}

		$replaces[] = new shopSeofilterConst();
		$replaces[] = new shopSeofilterRegionsReplacer();

		return $replaces;
	}

	private function seofilterVariablesParams()
	{
		return array(
			array('store_name', array('store_info', 'name')),
			array('store_phone', array('store_info', 'phone')),
			array('storefront_name', array('storefront', 'name')),
			array('category_name', array('category', 'name')),
			array('category_seo_name', array('category', 'seo_name')),
			array('parent_category_name', array('parent_category', 'seo_name')),
			array('seo_name', array('seo_name')),
			array('feature_name', array('feature_name')),
			array('value_name', array('value_name')),
			array('products_count', array('filter', 'products_count')),
			array('page_number', array('page_number')),
		);
	}
}