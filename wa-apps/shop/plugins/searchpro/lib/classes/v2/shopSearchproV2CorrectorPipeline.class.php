<?php

class shopSearchproV2CorrectorPipeline
{
	private static $grams_available;

	public function buildFinderParams(shopSearchproV2Settings $settings, $context, $category_id, shopSearchproEnv $env)
	{
		$cache_type = $context === 'page' ? 'page' : 'dropdown';
		$cache_ttl = $cache_type === 'page'
			? $settings->getPageCacheTtl()
			: $settings->getDropdownCacheTtl();

		$grams_status = (bool) $settings->get('grams_status');
		if ($grams_status && !$this->isGramsIndexAvailable()) {
			$grams_status = false;
		}

		return array(
			'mode' => $settings->get('search_mode'),
			'slice_query' => $settings->get('search_slice_query'),
			'rest_words' => $settings->get('search_rest_words'),
			'word_forms' => $settings->get('search_word_forms'),
			'form_break_symbols' => $settings->get('search_form_break_symbols'),
			'form_numbers' => $settings->get('search_form_numbers'),
			'form_strnum' => $settings->get('search_form_strnum'),
			'form_ignore_numstart' => $settings->get('search_form_ignore_numstart'),
			'form_min_length' => $settings->get('search_form_min_length'),
			'cache_type' => $cache_type,
			'cache_actuality' => $cache_ttl,
			'category_id' => (int) $category_id,
			'match_status' => $settings->get('match_status'),
			'brands_plugin' => $settings->get('dropdown_brands_plugin'),
			'corrector_status' => $settings->get('corrector_status'),
			'counts' => $this->buildCounts($settings, $context),
			'fields' => $this->buildFields($settings, $env, $context),
			'translate_status' => $settings->get('translate_status'),
			'grams_status' => $grams_status ? '1' : '0',
			'grams_mode' => $settings->get('grams_mode'),
			'keyboard_layout_status' => $settings->get('keyboard_layout_status'),
			'keyboard_layout_mode' => $settings->get('keyboard_layout_mode'),
			'combine_status' => $grams_status ? $settings->get('combine_status') : '',
		);
	}

	private function buildCounts(shopSearchproV2Settings $settings, $context)
	{
		if ($context === 'page') {
			return array(
				'products' => array(
					'min' => (int) $settings->get('page_products_min_count', 0),
					'max' => 0,
				),
			);
		}

		return array(
			'products' => array(
				'min' => (int) $settings->get('dropdown_products_min_count'),
				'max' => (int) $settings->get('dropdown_products_max_count'),
			),
			'categories' => array(
				'min' => (int) $settings->get('dropdown_categories_min_count'),
				'max' => (int) $settings->get('dropdown_categories_max_count'),
			),
			'brands' => array(
				'max' => (int) $settings->get('dropdown_brands_max_count'),
			),
			'popular' => array(
				'max' => (int) $settings->get('dropdown_popular_max_count'),
			),
			'history' => array(
				'max' => (int) $settings->get('dropdown_history_max_count'),
			),
		);
	}

	private function buildFields(shopSearchproV2Settings $settings, shopSearchproEnv $env, $context)
	{
		if ($context === 'page') {
			return array(
				'products' => array(
					'filled' => true,
					'event_frontend_products' => false,
					'pages' => $settings->get('page_products_pages_status'),
					'seopage_plugin' => $env->isEnabledSeopagePlugin() && $settings->get('page_products_seopage_plugin_status'),
				),
				'categories' => array(
					'hide_hidden' => true,
					'names' => true,
					'descriptions' => true,
					'seo_plugin' => $env->isEnabledSeoPlugin() && $settings->get('page_category_status'),
					'seo_names' => $env->isEnabledSeoPlugin(),
				),
			);
		}

		return array(
			'products' => array(
				'filled' => true,
				'event_frontend_products' => false,
				'pages' => $settings->get('dropdown_products_pages_status'),
				'seopage_plugin' => $env->isEnabledSeopagePlugin() && $settings->get('dropdown_products_seopage_plugin_status'),
			),
			'categories' => array(
				'hide_hidden' => $settings->get('dropdown_categories_hidden_hide_status'),
				'names' => $settings->get('dropdown_categories_names_status'),
				'descriptions' => $settings->get('dropdown_categories_descriptions_status'),
				'seo_plugin' => $env->isEnabledSeoPlugin() && $settings->get('dropdown_categories_seo_plugin_status'),
				'seo_names' => $env->isEnabledSeoPlugin() && $settings->get('dropdown_categories_seo_plugin_names'),
			),
		);
	}

	private function isGramsIndexAvailable()
	{
		if (self::$grams_available !== null) {
			return self::$grams_available;
		}

		try {
			$model = new shopSearchproGramsModel();
			$count = $model->query('SELECT COUNT(*) FROM shop_searchpro_grams')->fetchField();
			self::$grams_available = (int) $count > 0;
		} catch (Exception $e) {
			self::$grams_available = false;
		}

		return self::$grams_available;
	}
}
