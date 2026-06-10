<?php


class shopSeoWaProductEdit
{
	private $group_storefront_service;
	private $product_settings_service;
	private $storefront_field_service;
	private $category_field_service;
	private $product_field_service;
	private $fields_values_service;
	private $group_storefront_array_mapper;
	private $settings_array_mapper;
	private $field_array_mapper;
	private $fields_values_array_mapper;
	private $plugin_settings_service;
	
	public function __construct(
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoProductSettingsService $product_settings_service,
		shopSeoStorefrontFieldService $storefront_field_service,
		shopSeoCategoryFieldService $category_field_service,
		shopSeoProductFieldService $product_field_service,
		shopSeoProductFieldsValuesService $fields_values_service,
		shopSeoGroupStorefrontArrayMapper $group_storefront_array_mapper,
		shopSeoSettingsArrayMapper $settings_array_mapper,
		shopSeoFieldArrayMapper $field_array_mapper,
		shopSeoFieldsValuesArrayMapper $fields_values_array_mapper,
		shopSeoPluginSettingsService $plugin_settings_service
	) {
		$this->group_storefront_service = $group_storefront_service;
		$this->product_settings_service = $product_settings_service;
		$this->storefront_field_service = $storefront_field_service;
		$this->category_field_service = $category_field_service;
		$this->product_field_service = $product_field_service;
		$this->fields_values_service = $fields_values_service;
		$this->group_storefront_array_mapper = $group_storefront_array_mapper;
		$this->settings_array_mapper = $settings_array_mapper;
		$this->field_array_mapper = $field_array_mapper;
		$this->fields_values_array_mapper = $fields_values_array_mapper;
		$this->plugin_settings_service = $plugin_settings_service;
	}
	
	public function getState($product_id)
	{
		$product_fields = $this->product_field_service->getFields();
		
		return array(
			'product_id' => $product_id,
			'plugin_settings' => $this->getPluginSettings(),
			'groups_storefronts' => $this->getGroupsStorefronts(),
			'fields' => $this->getFields(),
			'general_settings' => $this->getGeneralSettings($product_id),
			'settings' => array(),
			'general_fields_values' => $this->getGeneralFieldsValues($product_id, $product_fields),
			'fields_values' => array(),
			'custom_variables' => $this->getCustomVariables(),
		);
	}
	
	public function render($state)
	{
		$path = wa('shop')->getAppPath('plugins/seo/templates/ProductSettings.html');
		$view = new waSmarty3View(wa());
		$plugin = wa('shop')->getPlugin('seo');
		
		$view->assign('state', $state);
		$view->assign('version', $plugin->getVersion());
		
		return $view->fetch($path);
	}
	
	public function save($category_id, $state)
	{
		$this->saveGeneralSettings($category_id, $state);
		$this->saveGeneralFieldsValues($category_id, $state);
		$this->saveSettings($category_id, $state);
		$this->saveFieldsValues($category_id, $state);
	}
	
	private function saveGeneralSettings($product_id, $state)
	{
		$settings = new shopSeoProductSettings();
		$settings->setGroupStorefrontId(0);
		$settings->setProductId($product_id);
		$this->settings_array_mapper->mapArray($settings, $state['general_settings']);
		$this->product_settings_service->store($settings);
	}
	
	private function saveGeneralFieldsValues($product_id, $state)
	{
		$fields_array = $state['fields']['product'];
		$fields = $this->field_array_mapper->mapArrays($fields_array);
		$fields_values = new shopSeoProductFieldsValues();
		$fields_values->setGroupStorefrontId(0);
		$fields_values->setProductId($product_id);
		$this->fields_values_array_mapper->mapArray($fields_values, $fields, $state['general_fields_values']);
		$this->fields_values_service->store($fields_values);
	}
	
	private function saveSettings($product_id, $state)
	{
		foreach ($state['settings'] as $i => $settings_array)
		{
			if (is_null($settings_array))
			{
				continue;
			}
			
			$group_storefront = $state['groups_storefronts'][$i];
			$settings = new shopSeoProductSettings();
			$settings->setGroupStorefrontId($group_storefront['id']);
			$settings->setProductId($product_id);
			$this->settings_array_mapper->mapArray($settings, $settings_array);
			$this->product_settings_service->store($settings);
		}
	}
	
	private function saveFieldsValues($product_id, $state)
	{
		$fields_array = $state['fields']['product'];
		$fields = $this->field_array_mapper->mapArrays($fields_array);
		
		foreach ($state['fields_values'] as $i => $fields_values_array)
		{
			if (is_null($fields_values_array))
			{
				continue;
			}
			
			$group_storefront = $state['groups_storefronts'][$i];
			$fields_values = new shopSeoProductFieldsValues();
			$fields_values->setGroupStorefrontId($group_storefront['id']);
			$fields_values->setProductId($product_id);
			$this->fields_values_array_mapper->mapArray($fields_values, $fields, $fields_values_array);
			$this->fields_values_service->store($fields_values);
		}
	}
	
	private function getPluginSettings()
	{
		$settings = $this->plugin_settings_service->getSettings();
		
		return $this->settings_array_mapper->mapSettings($settings);
	}
	
	private function getGroupsStorefronts()
	{
		$groups_storefronts = $this->group_storefront_service->getAll();
		$result = array();
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$result[] = $this->group_storefront_array_mapper->mapGroupStorefront($group_storefront);
		}
		
		return $result;
	}
	
	private function getGeneralSettings($product_id)
	{
		$product_settings = $this->product_settings_service->getGeneralByProductId($product_id);
		
		return $this->settings_array_mapper->mapSettings($product_settings);
	}
	
	private function getFields()
	{
		/** @var shopSeoFieldService[] $services */
		$services = array(
			'storefront' => $this->storefront_field_service,
			'category' => $this->category_field_service,
			'product' => $this->product_field_service,
		);
		$result = array();
		
		foreach (array_keys($services) as $type)
		{
			$fields = $services[$type]->getFields();
			$result[$type] = $this->field_array_mapper->mapFields($fields);
		}
		
		return $result;
	}
	
	private function getGeneralFieldsValues($product_id, $product_fields)
	{
		$values = $this->fields_values_service->getGeneralByProductIdAndFields($product_id, $product_fields);
		
		return $this->fields_values_array_mapper->mapFieldsValues($values);
	}
	
	private function getCustomVariables()
	{
		$custom_variables = array();
		
		foreach (array(
			'review',
			'page',
		) as $_type)
		{
			if (!isset($custom_variables[$_type]))
			{
				$custom_variables[$_type] = array();
			}
			
			$params_type = array(
				'type' => 'product',
				'group_type' => $_type,
			);
			$variables = wa('shop')->event(array('shop', 'seo_fetch_template_helper'), $params_type);
			
			foreach ($variables as $app_id => $_variables)
			{
				if (preg_match('/^(.*)\-plugin$/', $app_id, $matches))
				{
					$plugin_id = $matches[1];
					$name = wa('shop')->getPlugin($plugin_id)->getName();
				}
				else
				{
					$app_info = wa()->getAppInfo($app_id);
					$name = $app_info['name'];
				}
				
				foreach ($_variables as $i => $variable)
				{
					if (!is_array($variable))
					{
						$variable = array('variable' => $i, 'description' => $variable);
						$_variables[$i] = $variable;
					}
				}
				
				$_variables = array_values($_variables);
				
				$custom_variables[$_type][$name] = $_variables;
			}
		}
		
		return $custom_variables;
	}
}