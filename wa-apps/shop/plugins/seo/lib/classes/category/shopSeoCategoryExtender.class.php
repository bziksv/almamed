<?php


class shopSeoCategoryExtender
{
	private $category_data_source;
	private $category_settings_service;
	private $group_storefront_service;
	private $category_data_collector;
	private $category_data_renderer;
	private $env;
	private $plugin_settings_service;
	
	public function __construct(
		shopSeoCategoryDataSource $category_data_source,
		shopSeoCategorySettingsService $category_settings_service,
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoCategoryDataCollector $category_data_collector,
		shopSeoCategoryDataRenderer $category_data_renderer,
		shopSeoEnv $env,
		shopSeoPluginSettingsService $plugin_settings_service
	) {
		$this->category_data_source = $category_data_source;
		$this->category_settings_service = $category_settings_service;
		$this->group_storefront_service = $group_storefront_service;
		$this->category_data_collector = $category_data_collector;
		$this->category_data_renderer = $category_data_renderer;
		$this->env = $env;
		$this->plugin_settings_service = $plugin_settings_service;
	}
	
	public function extend($storefront, $category, $page)
	{
		$category_data = $this->category_data_collector->collect($storefront, $category['id'], $page != 1, $info);
		$category_data = $this->category_data_renderer->renderAll($storefront, $category['id'], $page, $category_data);
		
		$seo_name = $this->category_data_collector->collectSeoName($storefront, $category['id'], $info);
		$fields = $this->category_data_collector->collectFieldsValues($storefront, $category['id'], $info);
		
		if ($seo_name === '')
		{
			$seo_name = $category['name'];
		}
		
		$category['seo_name'] = $seo_name;
		$category['fields'] = $fields;
		$category['original_name'] = $category['name'];
		
		if ($category_data['h1'] !== '')
		{
			$category['name'] = $category_data['h1'];
		}
		
		$category['meta_title'] = $category_data['meta_title'];
		$category['meta_keywords'] = $category_data['meta_keywords'];
		$category['meta_description'] = $category_data['meta_description'];
		$category['description'] = $category_data['description'];
		
		if ($this->plugin_settings_service->getSettings()->category_additional_description_is_enabled)
		{
			$category['additional_description'] = $category_data['additional_description'];
		}
		
		if ($this->env->isSupportOg())
		{
			if ($category_data['og:title'] === '')
			{
				$category_data['og:title'] = $category_data['meta_title'];
			}
			
			if ($category_data['og:description'] === '')
			{
				$category_data['og:description'] = $category_data['meta_description'];
			}
			
			$category['og']['title'] = $category_data['og:title'];
			$category['og']['description'] = $category_data['og:description'];
		}
		
		return $category;
	}
}