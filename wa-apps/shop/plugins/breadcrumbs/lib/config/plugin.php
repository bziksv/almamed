<?php

return array(
	'name' => 'Навигация в хлебных крошках',
	'description' => 'Добавляет навигацию по каталогу к хлебным крошкам',
	'version' => '2.9',
	'img' => 'img/icon.png',
	'vendor' => 934303,
	'shop_settings' => true,
	'handlers' => array(
		'frontend_head' => 'handleFrontendHead',
		'breadcrumbs_frontend_breadcrumbs.*' => 'handleFrontendBreadcrumbs',
	),
);