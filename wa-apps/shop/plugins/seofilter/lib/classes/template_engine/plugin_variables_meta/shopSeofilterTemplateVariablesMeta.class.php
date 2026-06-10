<?php

class shopSeofilterTemplateVariablesMeta
{
	public function getMeta()
	{
		$variables_meta = array(
			'variables' => array(
				'{$seo_name}' => 'seo название значения характеристики',
				'{$value_name}' => 'название значения характеристики',
				'{$value_names[n]}' => 'название n-го значения характеристики',
				'{$feature_name}' => 'название характеристики',
				'{$category.name}' => 'название категории',
				'{$category.seo_name}' => 'seo название категории',
				'{$root_category.name}' => 'название корневой категории',
				'{$parent_category.name}' => 'название родительской категории',
				'{$parent_categories_names|sep:\' \'}' => 'путь к странице через пробел',
				'{$filter.products_count}' => 'количество товаров',
				'{$filter.max_price}' => 'максимальная цена товара на странице',
				'{$filter.min_price}' => 'минимальная цена товара на странице',
				'{$filter.max_price_without_currency}' => 'максимальная цена товара на странице (без HTML валюты)',
				'{$filter.min_price_without_currency}' => 'минимальная цена товара на странице (без HTML валюты)',
				//'{$filter.features}' => 'массив характеристик фильтра',
				'{$store_info.name}' => 'название магазина',
				'{$store_info.phone}' => 'телефон магазина',
				'{$storefront.name}' => 'название витрины',
				'{$page_number}' => 'номер страницы',
				'{$pages_count}' => 'количество страниц',
				'{$host}' => 'текущий домен',
			),
			'modifiers' => array(
				'|lower' => 'преобразует в нижний регистр',
				'|ucfirst' => 'преобразует первый символ в верхний регистр',
				'|lcfirst' => 'преобразует первый символ в нижний регистр',
			),
			'custom_variables' => array(),
		);

		foreach (shopSeofilterStorefrontFieldsModel::getAllFields() as $storefront_field_id => $storefront_field_name)
		{
			$variables_meta['variables']['{$storefront_field[' . $storefront_field_id . '].value}'] = $storefront_field_name;
		}

		foreach (shopSeofilterFilterFieldModel::getAllFields() as $filter_field_id => $filter_field_name)
		{
			$variables_meta['variables']['{$filter.field[' . $filter_field_id . '].value}'] = $filter_field_name;
		}

		$meta = new shopSeofilterCustomTemplateVariablesMeta();

		$custom_template_meta = $meta->getCustomTemplateVariablesMeta();
		if (count($custom_template_meta))
		{
			$variables_meta['custom_variables'] = $custom_template_meta;
		}

		return $variables_meta;
	}
}