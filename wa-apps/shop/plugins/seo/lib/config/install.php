<?php

$plugin_settings_service = shopSeoContext::getInstance()->getPluginSettingsService();
$plugin_settings = $plugin_settings_service->getSettings();
$plugin_settings->cache_is_enabled = true;
$plugin_settings->cache_variant = shopSeoPluginSettings::CACHE_VARIANT_7_DAYS;
$plugin_settings->page_number_is_enabled = true;
$plugin_settings_service->store($plugin_settings);

$storefront_settings_service = shopSeoContext::getInstance()->getStorefrontSettingsService();
$general_settings = $storefront_settings_service->getGeneralSettings();
$general_settings->home_page_meta_title = '{$store_info.name} — интернет-магазин';
$general_settings->category_meta_title = '{$category.seo_name} купить в интернет-магазине {$store_info.name}';
$general_settings->category_pagination_meta_title = '{$category.name} — страница {$page_number} | интернет-магазин {$store_info.name}';
$general_settings->product_meta_title = '{$product.name} купить в интернет-магазине {$store_info.name}';
$general_settings->product_review_meta_title = 'Отзывы о {$product.name} | интернет-магазин {$store_info.name}';
$general_settings->product_page_meta_title = '{$page.name} — {$product.name} | интернет-магазин {$store_info.name}';
$general_settings->page_meta_title = '{$page.name} | интернет-магазин {$store_info.name}';
$general_settings->tag_meta_title = '{$tag.name} | интернет-магазин {$store_info.name}';
$general_settings->brand_meta_title = '{$brand.name} | интернет-магазин {$store_info.name}';
$general_settings->brand_category_meta_title = '{$category.name} {$brand.name} купить в интернет-магазине {$store_info.name}';
$storefront_settings_service->store($general_settings);