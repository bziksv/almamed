<?php

class shopSeofilterDefaultTemplateModel extends waModel
{
	const CONTEXT_DEFAULT = 'DEFAULT';
	const CONTEXT_PAGINATION = 'PAGINATION';

	protected $table = 'shop_seofilter_default_template';

	public function getAsArray($storefront)
	{
		$rows = $this->getByField('storefront', $storefront, true);
		$result = array();

		foreach ($rows as $row)
		{
			$result[$row['name']] = $row['value'];
		}

		return $result;
	}

	/**
	 * @param $storefront
	 * @param null|array $contexts
	 * @return array[]
	 */
	public function getActiveTemplates($storefront, $contexts = null)
	{
		$template_settings = shopSeofilterDefaultTemplateSettingsModel::getSettings($storefront);

		$templates = array();

		if ($contexts === null || in_array(self::CONTEXT_DEFAULT, $contexts))
		{
			$templates[self::CONTEXT_DEFAULT] = $this->getTemplateValues($storefront, self::CONTEXT_DEFAULT);
		}


		if ($template_settings->pagination_is_enabled && ($contexts === null || in_array(self::CONTEXT_PAGINATION, $contexts)))
		{
			$templates[self::CONTEXT_PAGINATION] = $this->getTemplateValues($storefront, self::CONTEXT_PAGINATION);
		}

		uksort($templates, array($this, '_compareContextOrder'));

		return $templates;
	}

	/**
	 * @param string $storefront
	 * @param string $fields_context
	 * @return array
	 */
	private function getTemplateValues($storefront, $fields_context)
	{
		$storefront_templates_fields = $this->getAsArray($storefront);

		$template_fields = array();

		foreach ($this->templateFields() as $field)
		{
			switch ($fields_context)
			{
				case self::CONTEXT_PAGINATION:
					$template_fields[$field] = ifset($storefront_templates_fields[$field.'_pagination']);
					break;

				case self::CONTEXT_DEFAULT:
				default:
					$template_fields[$field] = ifset($storefront_templates_fields[$field]);
			}
		}

		$model = new shopSeofilterStorefrontFieldsValuesModel();
		$fields = $model->getForStorefront($storefront, $fields_context);

		foreach ($fields as $id => $value)
		{
			$template_fields['storefront_field_' . $id] = $value;
		}

		return $template_fields;
	}

	private function _compareContextOrder($context_1, $context_2)
	{
		if ($context_1 === $context_2)
		{
			return 0;
		}

		return $context_1 == self::CONTEXT_PAGINATION
			? -1
			: 1;
	}

	/**
	 * @return array
	 */
	private function templateFields()
	{
		return array(
			'storefront_name',
			'meta_title',
			'meta_keywords',
			'meta_description',
			'h1',
			'description',
			'additional_description',
		);
	}
}