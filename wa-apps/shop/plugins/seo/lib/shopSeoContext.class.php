<?php


class shopSeoContext
{
	private static $context;
	public static function getInstance()
	{
		if (!isset(self::$context))
		{
			self::$context = new shopSeoContext();
		}

		return self::$context;
	}

	private $storefront_service;
	private $category_params_model;
	private $wa_routing;
	private $group_storefront_service;
	private $group_category_service;
	private $storefront_settings_service;
	private $group_category_settings_service;
	private $category_settings_service;
	private $plugin_settings_service;
	private $storefront_field_service;
	private $category_field_service;
	private $product_field_service;
	private $request_handler_storage;
	private $storefront_fields_values_service;
	private $category_field_value_service;
	private $group_category_fields_values_service;
	private $product_field_value_service;
	private $product_settings_service;
	private $home_data_collector;
	private $category_data_collector;
	private $wa_source;
	private $category_extender;
	private $category_data_renderer;
	private $view_buffer_factory;
	private $storefront_data_collector;
	private $storefront_data_renderer;
	private $page_data_collector;
	private $tag_data_collector;
	private $tag_data_renderer;
	private $env;
	private $brand_data_collector;
	private $brand_data_renderer;
	private $brand_extender;
	private $category_inner_extender;
	private $storefront_view_buffer_modifier;
	private $pagination_view_buffer_modifier;
	private $category_view_buffer_modifier;
	private $brand_category_data_collector;
	private $brand_category_data_renderer;
	private $brand_category_extender;
	private $customs_view_buffer_modifier;
	private $product_extender;
	private $product_data_collector;
	private $product_data_renderer;
	private $product_view_buffer_modifier;
	private $product_review_data_collector;
	private $product_page_data_collector;
	private $response;
	private $wa_settings_page;
	private $env_array_mapper;
	private $category_model;
	private $settings_array_mapper;
	private $field_array_mapper;
	private $group_storefront_array_mapper;
	private $fields_values_array_mapper;
	private $group_category_array_mapper;
	private $wa_backend_category_dialog;
	private $wa_product_edit;
	
	public function getStorefrontService()
	{
		if (!isset($this->storefront_service))
		{
			$this->storefront_service = new shopSeoStorefrontService(
				$this->getStorefrontSource()
			);
		}
		
		return $this->storefront_service;
	}
	
	/**
	 * @return shopSeoStorefrontSource
	 */
	public function getStorefrontSource()
	{
		return $this->getWaRouting();
	}

	/**
	 * @return shopSeoWaRouting
	 */
	public function getRouting()
	{
		return $this->getWaRouting();
	}
	
	public function getGroupStorefrontService()
	{
		if (!isset($this->group_storefront_service))
		{
			$this->group_storefront_service = new shopSeoGroupStorefrontService(
				new shopSeoGroupStorefrontModel(),
				new shopSeoGroupStorefrontStorefrontModel(),
				$this->getStorefrontSettingsService(),
				$this->getStorefrontFieldsValuesService(),
				$this->getCategorySettingsService(),
				$this->getProductSettingsService(),
				$this->getCategoryFieldValueService(),
				$this->getProductFieldValueService()
			);
		}
		
		return $this->group_storefront_service;
	}
	
	public function getGroupCategoryService()
	{
		if (!isset($this->group_category_service))
		{
			$this->group_category_service = new shopSeoGroupCategoryService(
				new shopSeoGroupCategoryModel(),
				new shopSeoGroupCategoryStorefrontModel(),
				new shopSeoGroupCategoryCategoryModel(),
				$this->getGroupCategorySettingsService(),
				$this->getGroupCategoryFieldsValuesService()
			);
		}
		
		return $this->group_category_service;
	}
	
	public function getStorefrontSettingsService()
	{
		if (!isset($this->storefront_settings_service))
		{
			$this->storefront_settings_service = new shopSeoStorefrontSettingsService(
				new shopSeoStorefrontSettingsModel()
			);
		}
		
		return $this->storefront_settings_service;
	}
	
	public function getGroupCategorySettingsService()
	{
		if (!isset($this->group_category_settings_service))
		{
			$this->group_category_settings_service = new shopSeoGroupCategorySettingsService(
				new shopSeoGroupCategorySettingsModel()
			);
		}
		
		return $this->group_category_settings_service;
	}
	
	public function getCategorySettingsService()
	{
		if (!isset($this->category_settings_service))
		{
			$this->category_settings_service = new shopSeoCategorySettingsService(
				new shopSeoCategorySettingsModel()
			);
		}
		
		return $this->category_settings_service;
	}
	
	public function getPluginSettingsService()
	{
		if (!isset($this->plugin_settings_service))
		{
			$this->plugin_settings_service = new shopSeoPluginSettingsService(
				new shopSeoPluginSettingsModel(),
				new shopSeoPluginHiddenSettingsSource()
			);
		}
		
		return $this->plugin_settings_service;
	}
	
	public function getStorefrontFieldService()
	{
		if (!isset($this->storefront_field_service))
		{
			$this->storefront_field_service = new shopSeoStorefrontFieldService(
				new shopSeoStorefrontFieldModel(),
				$this->getStorefrontFieldsValuesService()
			);
		}
		
		return $this->storefront_field_service;
	}
	
	public function getCategoryFieldService()
	{
		if (!isset($this->category_field_service))
		{
			$this->category_field_service = new shopSeoCategoryFieldService(
				new shopSeoCategoryFieldModel(),
				$this->getCategoryFieldValueService(),
				$this->getGroupCategoryFieldsValuesService()
			);
		}
		
		return $this->category_field_service;
	}
	
	public function getProductFieldService()
	{
		if (!isset($this->product_field_service))
		{
			$this->product_field_service = new shopSeoProductFieldService(
				new shopSeoProductFieldModel(),
				$this->getProductFieldValueService()
			);
		}
		
		return $this->product_field_service;
	}
	
	public function getRequestHandlerStorage()
	{
		if (!isset($this->request_handler_storage))
		{
			$this->request_handler_storage = new shopSeoRequestHandlerService();
			$this->request_handler_storage->addChecker(
				new shopSeoWaRequestHandlerChecker()
			);
		}
		
		return $this->request_handler_storage;
	}
	
	public function getStorefrontFieldsValuesService()
	{
		if (!isset($this->storefront_fields_values_service))
		{
			$this->storefront_fields_values_service = new shopSeoStorefrontFieldsValuesService(
				new shopSeoStorefrontFieldValueModel()
			);
		}
		
		return $this->storefront_fields_values_service;
	}
	
	public function getCategoryFieldValueService()
	{
		if (!isset($this->category_field_value_service))
		{
			$this->category_field_value_service = new shopSeoCategoryFieldsValuesService(
				new shopSeoCategoryFieldValueModel()
			);
		}
		
		return $this->category_field_value_service;
	}
	
	public function getGroupCategoryFieldsValuesService()
	{
		if (!isset($this->group_category_fields_values_service))
		{
			$this->group_category_fields_values_service = new shopSeoGroupCategoryFieldsValuesService(
				new shopSeoGroupCategoryFieldValueModel()
			);
		}
		
		return $this->group_category_fields_values_service;
	}
	
	public function getProductFieldValueService()
	{
		if (!isset($this->product_field_value_service))
		{
			$this->product_field_value_service = new shopSeoProductFieldsValuesService(
				new shopSeoProductFieldValueModel()
			);
		}
		
		return $this->product_field_value_service;
	}
	
	public function getProductSettingsService()
	{
		if (!isset($this->product_settings_service))
		{
			$this->product_settings_service = new shopSeoProductSettingsService(
				new shopSeoProductSettingsModel()
			);
		}
		
		return $this->product_settings_service;
	}
	
	public function getHomeDataCollector()
	{
		if (!isset($this->home_data_collector))
		{
			$this->home_data_collector = new shopSeoHomeDataCollector(
				$this->getHomeMetaDataSource(),
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService()
			);
		}
		
		return $this->home_data_collector;
	}
	
	public function getCategoryDataCollector()
	{
		if (!isset($this->category_data_collector))
		{
			$this->category_data_collector = new shopSeoCategoryDataCollector(
				$this->getGroupStorefrontService(),
				$this->getGroupCategoryService(),
				$this->getCategorySettingsService(),
				$this->getCategoryDataSource(),
				$this->getPluginSettingsService(),
				$this->getCategoryFieldService(),
				$this->getCategoryFieldValueService(),
				$this->getGroupCategoryFieldsValuesService(),
				$this->getStorefrontSettingsService(),
				$this->getEnv()
			);
		}
		
		return $this->category_data_collector;
	}
	
	public function getCategoryExtender()
	{
		if (!isset($this->category_extender))
		{
			$this->category_extender = new shopSeoCategoryExtender(
				$this->getCategoryDataSource(),
				$this->getCategorySettingsService(),
				$this->getGroupStorefrontService(),
				$this->getCategoryDataCollector(),
				$this->getCategoryDataRenderer(),
				$this->getEnv(),
				$this->getPluginSettingsService()
			);
		}
		
		return $this->category_extender;
	}
	
	public function getCategoryDataRenderer()
	{
		if (!isset($this->category_data_renderer))
		{
			$this->category_data_renderer = new shopSeoCategoryDataRenderer(
				$this->getStorefrontViewBufferModifier(),
				$this->getPaginationViewBufferModifier(),
				$this->getCategoryViewBufferModifier(),
				$this->getViewBufferFactory(),
				$this->getCustomsViewBufferModifier()
			);
		}
		
		return $this->category_data_renderer;
	}
	
	/**
	 * @return shopSeoCategoryDataSource
	 */
	public function getCategoryDataSource()
	{
		return $this->getWaSource();
	}
	
	/**
	 * @return shopSeoViewBufferFactory
	 */
	public function getViewBufferFactory()
	{
		if (!isset($this->view_buffer_factory))
		{
			$this->view_buffer_factory = new shopSeoWaViewBufferFactory(
				$this->getStorefrontDataCollector()
			);
		}
		
		return $this->view_buffer_factory;
	}
	
	public function getStorefrontDataCollector()
	{
		if (!isset($this->storefront_data_collector))
		{
			$this->storefront_data_collector = new shopSeoStorefrontDataCollector(
				$this->getGroupStorefrontService(),
				$this->getStorefrontFieldService(),
				$this->getStorefrontFieldsValuesService()
			);
		}
		
		return $this->storefront_data_collector;
	}
	
	public function getStorefrontDataRenderer()
	{
		if (!isset($this->storefront_data_renderer))
		{
			$this->storefront_data_renderer = new shopSeoStorefrontDataRenderer(
				$this->getViewBufferFactory(),
				$this->getStorefrontViewBufferModifier(),
				$this->getCustomsViewBufferModifier()
			);
		}
		
		return $this->storefront_data_renderer;
	}
	
	public function getPageDataCollector()
	{
		if (!isset($this->page_data_collector))
		{
			$this->page_data_collector = new shopSeoPageDataCollector(
				$this->getPageDataSource(),
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService()
			);
		}
		
		return $this->page_data_collector;
	}
	
	public function getTagDataCollector()
	{
		if (!isset($this->tag_data_collector))
		{
			$this->tag_data_collector = new shopSeoTagDataCollector(
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService()
			);
		}
		
		return $this->tag_data_collector;
	}
	
	public function getTagDataRenderer()
	{
		if (!isset($this->tag_data_renderer))
		{
			$this->tag_data_renderer = new shopSeoTagDataRenderer(
				$this->getViewBufferFactory(),
				$this->getStorefrontViewBufferModifier(),
				$this->getPaginationViewBufferModifier(),
				$this->getCustomsViewBufferModifier()
			);
		}
		
		return $this->tag_data_renderer;
	}
	
	/**
	 * @return shopSeoEnv
	 */
	public function getEnv()
	{
		if (!isset($this->env))
		{
			$this->env = new shopSeoWaEnv();
		}
		
		return $this->env;
	}
	
	/**
	 * @return shopSeoHomeMetaDataSource
	 */
	public function getHomeMetaDataSource()
	{
		return $this->getWaSource();
	}
	
	/**
	 * @return shopSeoPageDataSource
	 */
	public function getPageDataSource()
	{
		return $this->getWaSource();
	}
	
	public function getBrandDataCollector()
	{
		if (!isset($this->brand_data_collector))
		{
			$this->brand_data_collector = new shopSeoBrandDataCollector(
				$this->getBrandDataSource(),
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService()
			);
		}
		
		return $this->brand_data_collector;
	}
	
	public function getBrandDataRenderer()
	{
		if (!isset($this->brand_data_renderer))
		{
			$this->brand_data_renderer = new shopSeoBrandDataRenderer(
				$this->getViewBufferFactory(),
				$this->getStorefrontViewBufferModifier(),
				$this->getPaginationViewBufferModifier(),
				$this->getCustomsViewBufferModifier()
			);
		}
		
		return $this->brand_data_renderer;
	}
	
	/**
	 * @return shopSeoBrandDataSource
	 */
	public function getBrandDataSource()
	{
		return $this->getWaSource();
	}
	
	public function getBrandExtender()
	{
		if (!isset($this->brand_extender))
		{
			$this->brand_extender = new shopSeoBrandExtender(
				$this->getBrandDataCollector(),
				$this->getBrandDataRenderer()
			);
		}
		
		return $this->brand_extender;
	}
	
	public function getCategoryInnerExtender()
	{
		if (!isset($this->category_inner_extender))
		{
			$this->category_inner_extender = new shopSeoCategoryInnerExtender(
				$this->getCategoryDataSource(),
				$this->getCategoryDataCollector()
			);
		}
		
		return $this->category_inner_extender;
	}
	
	public function getStorefrontViewBufferModifier()
	{
		if (!isset($this->storefront_view_buffer_modifier))
		{
			$this->storefront_view_buffer_modifier = new shopSeoStorefrontViewBufferModifier(
				$this->getStorefrontDataCollector()
			);
		}
		
		return $this->storefront_view_buffer_modifier;
	}
	
	public function getPaginationViewBufferModifier()
	{
		if (!isset($this->pagination_view_buffer_modifier))
		{
			$this->pagination_view_buffer_modifier = new shopSeoPaginationViewBufferModifier();
		}
		
		return $this->pagination_view_buffer_modifier;
	}
	
	public function getCategoryViewBufferModifier()
	{
		if (!isset($this->category_view_buffer_modifier))
		{
			$this->category_view_buffer_modifier = new shopSeoCategoryViewBufferModifier(
				$this->getCategoryDataSource(),
				$this->getCategoryInnerExtender()
			);
		}
		
		return $this->category_view_buffer_modifier;
	}
	
	public function getBrandCategoryDataCollector()
	{
		if (!isset($this->brand_category_data_collector))
		{
			$this->brand_category_data_collector = new shopSeoBrandCategoryDataCollector(
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService()
			);
		}
		
		return $this->brand_category_data_collector;
	}
	
	public function getBrandCategoryDataRenderer()
	{
		if (!isset($this->brand_category_data_renderer))
		{
			$this->brand_category_data_renderer = new shopSeoBrandCategoryDataRenderer(
				$this->getViewBufferFactory(),
				$this->getStorefrontViewBufferModifier(),
				$this->getPaginationViewBufferModifier(),
				$this->getCategoryViewBufferModifier(),
				$this->getCustomsViewBufferModifier()
			);
		}
		
		return $this->brand_category_data_renderer;
	}
	
	public function getBrandCategoryExtender()
	{
		if (!isset($this->brand_category_extender))
		{
			$this->brand_category_extender = new shopSeoBrandCategoryExtender(
				$this->getBrandCategoryDataCollector(),
				$this->getBrandCategoryDataRenderer(),
				$this->getCategoryDataCollector(),
				$this->getEnv()
			);
		}
		
		return $this->brand_category_extender;
	}
	
	public function getCustomsViewBufferModifier()
	{
		if (!isset($this->customs_view_buffer_modifier))
		{
			$this->customs_view_buffer_modifier = new shopSeoCustomsViewBufferModifier();
			$this->customs_view_buffer_modifier->addModifier(
				new shopSeoWaCustomViewBufferModifier()
			);
		}
		
		return $this->customs_view_buffer_modifier;
	}
	
	/**
	 * @return shopSeoProductDataSource
	 */
	public function getProductDataSource()
	{
		return $this->getWaSource();
	}
	
	public function getProductExtender()
	{
		if (!isset($this->product_extender))
		{
			$this->product_extender = new shopSeoProductExtender(
				$this->getProductDataCollector(),
				$this->getProductDataRenderer(),
				$this->getPluginSettingsService(),
				$this->getEnv()
			);
		}
		
		return $this->product_extender;
	}
	
	public function getProductDataCollector()
	{
		if (!isset($this->product_data_collector))
		{
			$this->product_data_collector = new shopSeoProductDataCollector(
				$this->getProductDataSource(),
				$this->getCategoryDataSource(),
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService(),
				$this->getGroupCategoryService(),
				$this->getProductSettingsService(),
				$this->getCategorySettingsService(),
				$this->getPluginSettingsService(),
				$this->getProductFieldService(),
				$this->getProductFieldValueService(),
				$this->getEnv()
			);
		}
		
		return $this->product_data_collector;
	}
	
	public function getProductDataRenderer()
	{
		if (!isset($this->product_data_renderer))
		{
			$this->product_data_renderer = new shopSeoProductDataRenderer(
				$this->getStorefrontViewBufferModifier(),
				$this->getProductViewBufferModifier(),
				$this->getViewBufferFactory(),
				$this->getCustomsViewBufferModifier()
			);
		}
		
		return $this->product_data_renderer;
	}
	
	public function getProductViewBufferModifier()
	{
		if (!isset($this->product_view_buffer_modifier))
		{
			$this->product_view_buffer_modifier = new shopSeoProductViewBufferModifier(
				$this->getProductDataSource(),
				$this->getCategoryDataSource(),
				$this->getCategoryInnerExtender(),
				$this->getProductInnerExtender()
			);
		}
		
		return $this->product_view_buffer_modifier;
	}
	
	public function getProductInnerExtender()
	{
		if (!isset($this->product_inner_extender))
		{
			$this->product_inner_extender = new shopSeoProductInnerExtender(
				$this->getProductDataCollector()
			);
		}
		
		return $this->product_inner_extender;
	}
	
	public function getProductReviewDataCollector()
	{
		if (!isset($this->product_review_data_collector))
		{
			$this->product_review_data_collector = new shopSeoProductReviewDataCollector(
				$this->getProductDataSource(),
				$this->getCategoryDataSource(),
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService(),
				$this->getGroupCategoryService(),
				$this->getProductSettingsService(),
				$this->getCategorySettingsService(),
				$this->getPluginSettingsService()
			);
		}
		
		return $this->product_review_data_collector;
	}
	
	public function getProductPageDataCollector()
	{
		if (!isset($this->product_page_data_collector))
		{
			$this->product_page_data_collector = new shopSeoProductPageDataCollector(
				$this->getProductDataSource(),
				$this->getCategoryDataSource(),
				$this->getGroupStorefrontService(),
				$this->getStorefrontSettingsService(),
				$this->getGroupCategoryService(),
				$this->getProductSettingsService(),
				$this->getCategorySettingsService(),
				$this->getPluginSettingsService()
			);
		}
		
		return $this->product_page_data_collector;
	}
	
	public function getResponse()
	{
		if (!isset($this->response))
		{
			$this->response = new shopSeoWaResponse(
				$this->getPluginSettingsService(),
				$this->getEnv()
			);
		}
		
		return $this->response;
	}
	
	public function getWaSettingsPage()
	{
		if (!isset($this->wa_settings_page))
		{
			$this->wa_settings_page = new shopSeoWaSettingsPage(
				$this->getEnv(),
				$this->getEnvArrayMapper(),
				$this->getStorefrontService(),
				$this->getCategoryModel(),
				$this->getPluginSettingsService(),
				$this->getSettingsArrayMapper(),
				$this->getStorefrontFieldService(),
				$this->getCategoryFieldService(),
				$this->getProductFieldService(),
				$this->getFieldArrayMapper(),
				$this->getGroupStorefrontService(),
				$this->getGroupStorefrontArrayMapper(),
				$this->getGroupCategoryService(),
				$this->getGroupCategoryArrayMapper(),
				$this->getStorefrontSettingsService()
			);
		}
		
		return $this->wa_settings_page;
	}
	
	public function getEnvArrayMapper()
	{
		if (!isset($this->env_array_mapper))
		{
			$this->env_array_mapper = new shopSeoEnvArrayMapper();
		}
		
		return $this->env_array_mapper;
	}
	
	public function getSettingsArrayMapper()
	{
		if (!isset($this->settings_array_mapper))
		{
			$this->settings_array_mapper = new shopSeoSettingsArrayMapper();
		}
		
		return $this->settings_array_mapper;
	}
	
	public function getFieldArrayMapper()
	{
		if (!isset($this->field_array_mapper))
		{
			$this->field_array_mapper = new shopSeoFieldArrayMapper();
		}
		
		return $this->field_array_mapper;
	}
	
	public function getGroupStorefrontArrayMapper()
	{
		if (!isset($this->group_storefront_array_mapper))
		{
			$this->group_storefront_array_mapper = new shopSeoGroupStorefrontArrayMapper(
				$this->getSettingsArrayMapper(),
				$this->getFieldsValuesArrayMapper()
			);
		}
		
		return $this->group_storefront_array_mapper;
	}
	
	public function getFieldsValuesArrayMapper()
	{
		if (!isset($this->fields_values_array_mapper))
		{
			$this->fields_values_array_mapper = new shopSeoFieldsValuesArrayMapper();
		}
		
		return $this->fields_values_array_mapper;
	}
	
	public function getGroupCategoryArrayMapper()
	{
		if (!isset($this->group_category_array_mapper))
		{
			$this->group_category_array_mapper = new shopSeoGroupCategoryArrayMapper(
				$this->getSettingsArrayMapper(),
				$this->getFieldsValuesArrayMapper()
			);
		}
		
		return $this->group_category_array_mapper;
	}
	
	public function getWaBackendCategoryDialog()
	{
		if (!isset($this->wa_backend_category_dialog))
		{
			$this->wa_backend_category_dialog = new shopSeoWaBackendCategoryDialog(
				$this->getGroupStorefrontService(),
				$this->getCategorySettingsService(),
				$this->getStorefrontFieldService(),
				$this->getCategoryFieldService(),
				$this->getProductFieldService(),
				$this->getCategoryFieldValueService(),
				$this->getGroupStorefrontArrayMapper(),
				$this->getSettingsArrayMapper(),
				$this->getFieldArrayMapper(),
				$this->getFieldsValuesArrayMapper(),
				$this->getPluginSettingsService(),
				$this->getCategoryDataSource()
			);
		}
		
		return $this->wa_backend_category_dialog;
	}
	
	public function getWaProductEdit()
	{
		if (!isset($this->wa_product_edit))
		{
			$this->wa_product_edit = new shopSeoWaProductEdit(
				$this->getGroupStorefrontService(),
				$this->getProductSettingsService(),
				$this->getStorefrontFieldService(),
				$this->getCategoryFieldService(),
				$this->getProductFieldService(),
				$this->getProductFieldValueService(),
				$this->getGroupStorefrontArrayMapper(),
				$this->getSettingsArrayMapper(),
				$this->getFieldArrayMapper(),
				$this->getFieldsValuesArrayMapper(),
				$this->getPluginSettingsService()
			);
		}
		
		return $this->wa_product_edit;
	}
	
	private function getWaSource()
	{
		if (!isset($this->wa_source))
		{
			$this->wa_source = new shopSeoWaSource(
				$this->getCategoryModel(),
				$this->getCategoryParamsModel(),
				$this->getEnv()->isSupportOg() ? new shopCategoryOgModel() : null,
				$this->getPluginSettingsService(),
				new shopPageModel(),
				new shopPageParamsModel(),
				new shopProductModel(),
				new shopProductPagesModel(),
				$this->getEnv(),
				$this->getEnv()->isEnabledProductbrands() ? new shopProductbrandsModel() : null
			);
		}
		
		return $this->wa_source;
	}
	
	private function getWaRouting()
	{
		if (!isset($this->wa_routing))
		{
			$this->wa_routing = new shopSeoWaRouting();
		}
		
		return $this->wa_routing;
	}
	
	private function getCategoryModel()
	{
		if (!isset($this->category_model))
		{
			$this->category_model = new shopCategoryModel();
		}
		
		return $this->category_model;
	}

	private function getCategoryParamsModel()
	{
		if (!isset($this->category_params_model))
		{
			$this->category_params_model = new shopCategoryParamsModel();
		}

		return $this->category_params_model;
	}
}
