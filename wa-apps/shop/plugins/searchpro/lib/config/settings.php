<?php

/**
 * Настройки плагина по умолчанию
 */

return array(
	'basic' => array(
		'status' => '1'
	),
	'storefronts' => array(
		'*' => array(
			'corrector_status' => 'logical',

			'match_status' => '1',
			'translate_status' => '1',
			'grams_status' => '1',
			'grams_mode' => 'like',
			'keyboard_layout_status' => '1',
			'keyboard_layout_mode' => 'smart',
			'combine_status' => 'KeyboardLayout+Grams',

			'button' => 'Найти',
			'placeholder' => 'Введите запрос...',
			'clear_button' => '1',
			'category_filter_status' => '1',
			'category_filter_deep' => '1',

			'search_mode' => 'shop',
			'search_slice_query' => '1',
			'search_rest_words' => '1',
			'search_word_forms' => '1',
			'search_form_break_symbols' => '/.!?|<>[]«»()-',
			'search_form_numbers' => '0',
			'search_form_strnum' => '0',
			'search_form_ignore_numstart' => '0',
			'search_form_min_length' => '3',

			'page_products_status' => '1',
			'page_products_min_count' => '3',
			'page_products_pages_status' => '0',
			'page_products_seopage_plugin_status' => '0',

			'page_filter_status' => '1',
			'page_filter_disabled_features' => array(),
			'page_filter_features_sort' => array(),
			'page_filter_selectable_status' => '0',
			'page_filter_price_status' => '1',
			'page_category_status' => '1',
			'page_category_max_count' => '',
			'page_category_mode' => 'inline',
			'page_category_inline_mode_style' => 'inline',
			'page_category_image' => '',
			'page_sort_status' => '1',
			'page_empty_products_status' => '1',
			'page_empty_products_set' => 'bestsellers',
			'page_empty_products_max_length' => '',
			'page_empty_popular_status' => '1',
			'page_empty_popular_max_length' => '10',
			'page_results_cache' => '0',

			'dropdown_status' => '1',
			'dropdown_min_length' => '3',
			'dropdown_highlight' => '1',
			'dropdown_entities_sort' => array('products', 'categories', 'brands', 'popular', 'history'),
			'dropdown_products_status' => '1',
			'dropdown_event_frontend_products' => '1',
			'dropdown_products_min_count' => '3',
			'dropdown_products_max_count' => '10',
			'dropdown_products_image_status' => '1',
			'dropdown_products_summary_status' => '1',
			'dropdown_products_price_status' => '1',
			'dropdown_products_pages_status' => '0',
			'dropdown_products_seopage_plugin_status' => '0',
			'dropdown_results_cache' => '10800',

			'dropdown_categories_status' => '1',
			'dropdown_categories_hidden_hide_status' => '1',
			'dropdown_categories_min_count' => '3',
			'dropdown_categories_max_count' => '5',
			'dropdown_categories_products_status' => '0',
			'dropdown_categories_names_status' => '1',
			'dropdown_categories_descriptions_status' => '1',
			'dropdown_categories_seo_plugin_status' => '0',
			'dropdown_categories_seo_plugin_names' => '0',
			//'dropdown_categories_seofilter_plugin_status' => '1',

			'dropdown_brands_status' => '1',
			'dropdown_brands_plugin' => 'brand',
			'dropdown_brands_max_count' => '5',

			'dropdown_popular_is_visible' => '1',
			'dropdown_popular_status' => '1',
			'dropdown_popular_max_count' => '5',

			'dropdown_history_is_visible' => '1',
			'dropdown_history_status' => '1',
			'dropdown_history_max_count' => '5',
			'dropdown_history_delete_status' => '1',

			'detector_rules' => array()
		)
	),
	'themes' => array(
		'*' => array(
			'design_custom_fonts_status' => '1',
			'design_field_width' => '450',
			'design_field_main_color' => '#f2994a',
			'design_field_border_color' => '#e7e7e7',
			'design_field_hint_color' => '#828282',
			'design_filter_position' => 'right',
			'design_filter_ajax_status' => '0',
			'design_filter_custom' => '1',
			'design_page_main_color' => '#f2994a',
			'design_page_main_sub_color' => '#fef5ec',
			'design_page_border_color' => '#e7e7e7',
			'design_page_border_sub_color' => '#f2f2f2',
			'design_page_hint_color' => '#828282',
			'design_filter_main_color' => '#f2994a',
			'design_filter_hint_color' => '#828282',
			'design_filter_border_color' => '#e7e7e7',
			'design_filter_border_sub_color' => '#f2f2f2',
		)
	)
);
