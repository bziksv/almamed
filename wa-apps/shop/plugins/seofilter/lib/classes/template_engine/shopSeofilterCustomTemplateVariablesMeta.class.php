<?php

class shopSeofilterCustomTemplateVariablesMeta
{
	public function getCustomTemplateVariablesMeta()
	{
		$custom_variables = array();
		$variables = wa('shop')->event(array('shop', 'seofilter_fetch_template_helper'), $type);

		foreach ($variables as $app_id => $_variables)
		{
			if (preg_match('/^(.*)\-plugin$/', $app_id, $matches))
			{
				$plugin_id = $matches[1];
				$name = wa('shop')->getPlugin($plugin_id)->getName();
			}
			else
			{
				$app_info = wa()->getAppInfo($app_id);
				$name = $app_info['name'];
			}

			$custom_variables[$name] = $variables[$app_id];
		}

		$seo_helper = new shopSeofilterSeoHelper();

		$seo_category_fields = array();
		foreach ($seo_helper->getCategoryCustomFieldNames() as $field_id => $field_name)
		{
			$seo_category_fields['{$category.fields[' . $field_id . '].value}'] = $field_name;
		}

		if (count($seo_category_fields) > 0)
		{
			$custom_variables['SEO-оптимизация'] = $seo_category_fields;
		}

		return $custom_variables;
	}
}