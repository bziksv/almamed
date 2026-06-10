<?php

return array(
	'name' => 'SEO-оптимизация',
	'description' => 'Быстрая и гибкая оптимизация вашего магазина',
	'img' => 'img/seo3.png',
	'vendor' => '934303',
	'shop_settings' => true,
	'importexport' => true,
	'version' => '3.6',
	'handlers' => array(
		'backend_category_dialog' => 'backendCategoryDialog',
		'category_save' => 'categorySave',
		'backend_product_edit' => 'backendProductEdit',
		'product_save' => 'productSave',
		'frontend_nav' => 'frontendNav', // Не совсем то что надо, но других вариантов нету.
		'frontend_search' => 'frontendSearch',
		'frontend_homepage' => 'frontendHomepage',
		'frontend_category' => 'frontendCategory',
		'frontend_product' => 'frontendProduct',
		'frontend_head' => 'frontendHead',
		'routing' => 'routing',
	),
);
