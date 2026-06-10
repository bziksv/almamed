<?php


class shopSeoProductExtender
{
	private $product_data_collector;
	private $product_data_renderer;
	private $plugin_settings_service;
	private $env;
	
	public function __construct(
		shopSeoProductDataCollector $product_data_collector,
		shopSeoProductDataRenderer $product_data_renderer,
		shopSeoPluginSettingsService $plugin_settings_service,
		shopSeoEnv $env
	)
	{
		$this->product_data_collector = $product_data_collector;
		$this->product_data_renderer = $product_data_renderer;
		$this->plugin_settings_service = $plugin_settings_service;
		$this->env = $env;
	}
	
	public function extend($storefront, $product)
	{
		$product_data = $this->product_data_collector->collect($storefront, $product['id'], $info);
		$product_data = $this->product_data_renderer->renderAll($storefront, $product['id'], $product_data);
		$seo_name = $this->product_data_collector->collectSeoName($storefront, $product['id'], $info);
		$fields = $this->product_data_collector->collectFieldsValues($storefront, $product['id'], $info);
		
		if ($seo_name === '')
		{
			$seo_name = $product['name'];
		}
		
		$product['seo_name'] = $seo_name;
		$product['fields'] = $fields;
		$product['meta_title'] = $product_data['meta_title'];
		$product['meta_description'] = $product_data['meta_description'];
		$product['meta_keywords'] = $product_data['meta_keywords'];
		$product['original_name'] = $product['name'];
		
		if ($product_data['h1'] !== '')
		{
			$product['name'] = $product_data['h1'];
		}
		
		$product['original_description'] = $product['description'];
		$product['description'] = $product_data['description'];
		
		if ($this->plugin_settings_service->getSettings()->product_additional_description_is_enabled)
		{
			$product['additional_description'] = $product_data['additional_description'];
		}
		
		if ($this->env->isSupportOg())
		{
			if ($product_data['og:title'] === '')
			{
				$product_data['og:title'] = $product_data['meta_title'];
			}
			
			if ($product_data['og:description'] === '')
			{
				$product_data['og:description'] = $product_data['meta_description'];
			}
			
			$og = isset($product['og']) ? $product['og'] : array();
			$og['title'] = $product_data['og:title'];
			$og['description'] = $product_data['og:description'];
			$product['og'] = $og;
		}
		
		return $product;
	}
}