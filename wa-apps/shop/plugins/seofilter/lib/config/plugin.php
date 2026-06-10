<?php

return array(
	'name' => 'SEO-фильтр',
	'description' => 'Оптимизация результатов фильтра',
	'img' => 'img/seofilter.png',
	'vendor' => '934303',
	'frontend' => true,
	'shop_settings' => true,
	'version' => '2.31',
	'handlers' => array(
		'seo_assign_case' => 'handleSeoAssignCase',

		'frontend_category' => 'handleFrontendCategory',
		'frontend_head' => 'handleFrontendHead',
		'sitemap' => 'handleSitemap',
		'backend_menu' => 'handleBackendMenu',
		'routing' => 'routing',
		'rights.config' => 'handleRightsConfig',
		'app_sitemap_index_sitemap' => 'handleAppSitemapIndexSitemap',
		'app_sitemap_structure' => 'handleAppSitemapStructure',

		/**
		 * для обновления кеша sitemap
		 */
		'product_save' => 'handleProductSave',
		'product_sku_delete' => 'handleProductSkuDelete',
		'product_delete' => 'handleProductDelete',
		'product_mass_update' => 'handleProductMassUpdate',
		'category_delete' => 'handleCategoryDelete',
		'category_save' => 'handleCategorySave',
	),
);
