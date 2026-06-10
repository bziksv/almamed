<?php

class shopSeofilterPluginSettingsTemplateRulesController extends shopSeofilterBackendJsonController
{
	public function execute()
	{
		$storefront = waRequest::post('storefront');
		$template_rule_model = new shopSeofilterDefaultTemplateModel();
		$template_rules = $template_rule_model->getAsArray(waRequest::post('storefront'));
		$data = array();

		if ($storefront != '*')
		{
			$data['storefront_name'] = ifset($template_rules['storefront_name'], '');
			$data['storefront_name_pagination'] = ifset($template_rules['storefront_name_pagination'], '');
			$data['settings'] = shopSeofilterDefaultTemplateSettingsModel::getSettings($storefront)->getRawSettings();
			$fields_model = new shopSeofilterStorefrontFieldsValuesModel();
			$data['fields'] = array(
				shopSeofilterDefaultTemplateModel::CONTEXT_DEFAULT => $fields_model->getForStorefront($storefront, shopSeofilterDefaultTemplateModel::CONTEXT_DEFAULT),
				shopSeofilterDefaultTemplateModel::CONTEXT_PAGINATION => $fields_model->getForStorefront($storefront, shopSeofilterDefaultTemplateModel::CONTEXT_PAGINATION),
			);

			unset($template_rules['storefront_name']);
		}

		$data['templates'] = $template_rules;

		$this->response = $data;
	}
}