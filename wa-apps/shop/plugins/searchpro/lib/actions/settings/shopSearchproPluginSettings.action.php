<?php

class shopSearchproPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$plugin = shopSearchproPlugin::getInstance('settings');
		$version = floatval($plugin->getVersion());

		$plugin_url = $plugin->getPluginStaticUrl(true);
		$settings = $plugin->getSettings();

		$themes = $plugin->getEnv()->getThemes();

		$templates_instance = new shopSearchproTemplates();
		$templates = $templates_instance->get();
		$template_names = $templates_instance->getNames();

		$query_storage = new shopSearchproQueryStorage();
		$queries_per_page = shopSearchproQueryStorage::BACKEND_QUERIES_PER_PAGE;
		$queries = $query_storage->getQueries(0, $queries_per_page);
		$queries_count = $query_storage->getQueriesCount();

		$storefronts = $plugin->getEnv()->getStorefronts();
		$storefront_groups = $plugin->getEnv()->getStorefrontGroups();

		$is_enabled_seopage_plugin = $plugin->getEnv()->isEnabledSeopagePlugin();
		$is_enabled_seo_plugin = $plugin->getEnv()->isEnabledSeoPlugin();
		$is_enabled_seofilter_plugin = $plugin->getEnv()->isEnabledSeofilterPlugin();
		$is_enabled_brand_plugin = $plugin->getEnv()->isEnabledBrandPlugin();
		$is_enabled_productbrands_plugin = $plugin->getEnv()->isEnabledProductbrandsPlugin();

		$plugin_name = 'searchpro';

		$grams_model = new shopSearchproGramsModel();
		$grams_count = $grams_model->count();

		$set_model = new shopSetModel();
		$sets = $set_model->getAll();

		$features_helper = new shopSearchproFeaturesHelper();
		$features = $features_helper->getFeaturesCanFilter('id');

		$vars = compact('plugin_name', 'plugin_url', 'themes', 'settings', 'templates', 'template_names', 'storefronts', 'storefront_groups', 'grams_count', 'version', 'is_enabled_seopage_plugin', 'is_enabled_seo_plugin', 'is_enabled_seofilter_plugin', 'is_enabled_brand_plugin', 'is_enabled_productbrands_plugin', 'queries', 'queries_count', 'queries_per_page', 'sets', 'features');

		$this->view->assign($vars);
		$this->view->assign('plugin', $vars);
	}
}
