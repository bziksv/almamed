<?php

class shopSeofilterPluginSettingsSaveController extends waJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');
		$state = json_decode($state_json, true);

		$this->saveBasicSettings(ifset($state['basic_settings'], array()));
		$this->saveTemplateRules(ifset($state['template_rules'], array()));
		$this->saveStorefrontFields(ifset($state['storefront_fields'], array()));
		$this->saveFilterFields(ifset($state['filter_fields'], array()));
		$this->saveProductfiltersSettings(ifset($state['productfilters_state'], array()));
	}

	private function saveBasicSettings($basic_settings)
	{
		$basic_settings_model = new shopSeofilterBasicSettingsModel();

		$basic_settings_model->saveSettings($basic_settings);
	}

	private function saveTemplateRules($template_rules)
	{
		$template_rule_model = new shopSeofilterDefaultTemplateModel();
		$template_settings_model = new shopSeofilterDefaultTemplateSettingsModel();
		$storefront_fields_values_model = new shopSeofilterStorefrontFieldsValuesModel();

		foreach (ifset($template_rules['data'], array()) as $storefront => $data)
		{
			if ($storefront != '*')
			{
				$template_rule_model->replace(
					array(
						'storefront' => $storefront,
						'name' => 'storefront_name',
						'value' => ifset($data['storefront_name']),
					)
				);

				$template_rule_model->replace(
					array(
						'storefront' => $storefront,
						'name' => 'storefront_name_pagination',
						'value' => ifset($data['storefront_name_pagination']),
					)
				);
			}

			$templates = ifset($data['templates'], array());
			unset($templates['storefront_name']);
			unset($templates['storefront_name_pagination']);
			foreach ($templates as $name => $value)
			{
				$template_rule_model->replace(
					array(
						'storefront' => $storefront,
						'name' => $name,
						'value' => $value,
					)
				);
			}

			foreach (ifset($data['settings'], array()) as $name => $value)
			{
				$template_settings_model->replace(array(
					'storefront' => $storefront,
					'name' => $name,
					'value' => $value,
				));
			}



			foreach (ifset($data['fields'], array()) as $field_context => $field_values)
			{
				foreach (ifset($field_values, array()) as $field_id => $value)
				{
					$storefront_fields_values_model->replace(
						array(
							'storefront' => $storefront,
							'field_id' => $field_id,
							'context' => $field_context,
							'value' => $value,
						)
					);
				}
			}
		}
	}

	private function saveStorefrontFields($storefront_fields)
	{
		$template_rule_model = new shopSeofilterDefaultTemplateModel();
		$fields_model = new shopSeofilterStorefrontFieldsModel();
		$source_fields = shopSeofilterStorefrontFieldsModel::getAllFields();
		$storefront_fields_values_model = new shopSeofilterStorefrontFieldsValuesModel();

		foreach (ifset($storefront_fields['fields'], array()) as $field_id => $name)
		{
			$fields_model->replace(
				array(
					'id' => $field_id,
					'name' => $name,
				)
			);

			if (isset($source_fields[$field_id]))
			{
				unset($source_fields[$field_id]);
			}
		}

		foreach ($source_fields as $field_id => $name)
		{
			$fields_model->deleteById($field_id);
			$storefront_fields_values_model->deleteByField('field_id', $field_id);
		}

		$this->response['modified_storefronts'] = array_keys($template_rule_model->select('DISTINCT storefront')->fetchAll('storefront'));
	}

	private function saveFilterFields($filter_fields)
	{
		$fields_model = new shopSeofilterFilterFieldModel();
		$source_fields = shopSeofilterFilterFieldModel::getAllFields();

		$sort = 1;

		foreach (ifset($filter_fields['fields'], array()) as $field_id => $name)
		{
			$fields_model->replace(
				array(
					'id' => $field_id,
					'name' => $name,
					'sort' => $sort++,
				)
			);

			if (isset($source_fields[$field_id]))
			{
				unset($source_fields[$field_id]);
			}
		}

		foreach ($source_fields as $field_id => $name)
		{
			$fields_model->deleteById($field_id);
		}
	}

	private function saveProductfiltersSettings($productfilters_state)
	{
		$settings_state = new shopSeofilterProductfiltersSettingsState();

		$settings_state->save($productfilters_state);
	}
}