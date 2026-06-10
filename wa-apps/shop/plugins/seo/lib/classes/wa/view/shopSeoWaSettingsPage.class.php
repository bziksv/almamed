<?php


class shopSeoWaSettingsPage
{
	private $env;
	private $env_array_mapper;
	private $storefront_service;
	private $category_model;
	private $plugin_settings_service;
	private $settings_array_mapper;
	private $storefront_field_service;
	private $category_field_service;
	private $product_field_service;
	private $field_array_mapper;
	private $group_storefront_service;
	private $group_storefront_array_mapper;
	private $group_category_service;
	private $group_category_array_mapper;
	private $storefront_settings_service;
	
	public function __construct(
		shopSeoEnv $env,
		shopSeoEnvArrayMapper $env_array_mapper,
		shopSeoStorefrontService $storefront_service,
		shopCategoryModel $category_model,
		shopSeoPluginSettingsService $plugin_settings_service,
		shopSeoSettingsArrayMapper $settings_array_mapper,
		shopSeoStorefrontFieldService $storefront_field_service,
		shopSeoCategoryFieldService $category_field_service,
		shopSeoProductFieldService $product_field_service,
		shopSeoFieldArrayMapper $field_array_mapper,
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoGroupStorefrontArrayMapper $group_storefront_array_mapper,
		shopSeoGroupCategoryService $group_category_service,
		shopSeoGroupCategoryArrayMapper $group_category_array_mapper,
		shopSeoStorefrontSettingsService $storefront_settings_service
	) {
		$this->env = $env;
		$this->env_array_mapper = $env_array_mapper;
		$this->storefront_service = $storefront_service;
		$this->category_model = $category_model;
		$this->plugin_settings_service = $plugin_settings_service;
		$this->settings_array_mapper = $settings_array_mapper;
		$this->storefront_field_service = $storefront_field_service;
		$this->category_field_service = $category_field_service;
		$this->product_field_service = $product_field_service;
		$this->field_array_mapper = $field_array_mapper;
		$this->group_storefront_service = $group_storefront_service;
		$this->group_storefront_array_mapper = $group_storefront_array_mapper;
		$this->group_category_service = $group_category_service;
		$this->group_category_array_mapper = $group_category_array_mapper;
		$this->storefront_settings_service = $storefront_settings_service;
	}
	
	public function getState($loaded_groups_storefronts_ids, $loaded_groups_categories_ids)
	{
		$storefront_fields = $this->storefront_field_service->getFields();
		$category_fields = $this->category_field_service->getFields();
		
		return array(
			'env' => $this->getEnv(),
			'storefronts' => $this->getStorefronts(),
			'categories' => $this->getCategories(),
			'plugin_settings' => $this->getPluginSettings(),
			'fields' => $this->getFields(),
			'groups_storefronts' => $this->getGroupsStorefronts($loaded_groups_storefronts_ids, $storefront_fields),
			'groups_categories' => $this->getGroupsCategories($loaded_groups_categories_ids, $category_fields),
			'general_settings' => $this->getGeneralSettings(),
			'custom_variables' => $this->getCustomVariables(),
		);
	}
	
	public function save($state, &$loaded_groups_storefronts_ids, &$loaded_groups_categories_ids)
	{
		$this->savePluginSettings($state);
		$this->saveGeneralSettings($state);
		$this->saveFields($state, $fields);
		$this->saveGroupsStorefronts($state, $fields['storefront'], $loaded_groups_storefronts_ids);
		$this->updateGroupsStorefrontsSort();
		$this->saveGroupsCategories($state, $fields['category'], $loaded_groups_categories_ids);
		$this->updateGroupsCategoriesSort();
	}
	
	private function savePluginSettings($state)
	{
		$plugin_settings = new shopSeoPluginSettings();
		$this->settings_array_mapper->mapArray($plugin_settings, $state['plugin_settings']);
		$this->plugin_settings_service->store($plugin_settings);
	}
	
	private function saveFields($state, &$fields)
	{
		/** @var shopSeoFieldService[] $services */
		$services = array(
			'storefront' => $this->storefront_field_service,
			'category' => $this->category_field_service,
			'product' => $this->product_field_service,
		);
		$fields = array();
		
		foreach ($services as $type => $service)
		{
			$fields[$type] = array();
			$fields_array = $state['fields'][$type];
			
			foreach ($fields_array as $field_array)
			{
				$field = $this->field_array_mapper->mapArray($field_array);
				
				if (isset($field_array['is_deleted']))
				{
					$service->delete($field);
				}
				else
				{
					$service->store($field);
				}
				
				$fields[$type][] = $field;
			}
		}
	}
	
	private function saveGeneralSettings($state)
	{
		$settings = new shopSeoStorefrontSettings();
		$settings->setGroupId(0);
		$this->settings_array_mapper->mapArray($settings, $state['general_settings']);
		$this->storefront_settings_service->store($settings);
	}
	
	private function saveGroupsStorefronts($state, $storefront_fields, &$loaded_groups_ids)
	{
		$loaded_groups_ids = array();
		
		foreach ($state['groups_storefronts'] as $group_storefront_array)
		{
			$group_storefront = $this->group_storefront_array_mapper->mapArray($group_storefront_array,
				$storefront_fields);
			
			if ($group_storefront->getFieldsValues())
			{
				$this->storefront_field_service->correctFieldsValues($group_storefront->getFieldsValues());
			}
			
			if (isset($group_storefront_array['is_deleted']))
			{
				$this->group_storefront_service->delete($group_storefront);
			}
			else
			{
				$this->group_storefront_service->store($group_storefront);
				
				if (isset($group_storefront_array['settings']))
				{
					$loaded_groups_ids[] = $group_storefront->getId();
				}
			}
		}
	}
	
	private function updateGroupsStorefrontsSort()
	{
		$this->group_storefront_service->updateSort();
	}
	
	private function saveGroupsCategories($state, $category_fields, &$loaded_groups_ids)
	{
		$loaded_groups_ids = array();
		
		foreach ($state['groups_categories'] as $group_category_array)
		{
			$group_category = $this->group_category_array_mapper->mapArray($group_category_array,
				$category_fields);
			
			if ($group_category->getFieldsValues())
			{
				$this->category_field_service->correctFieldsValues($group_category->getFieldsValues());
			}
			
			if (isset($group_category_array['is_deleted']))
			{
				$this->group_category_service->delete($group_category);
			}
			else
			{
				$this->group_category_service->store($group_category);
				
				if (isset($group_category_array['settings']))
				{
					$loaded_groups_ids[] = $group_category->getId();
				}
			}
		}
	}
	
	private function updateGroupsCategoriesSort()
	{
		$this->group_category_service->updateSort();
	}
	
	private function getEnv()
	{
		return $this->env_array_mapper->mapEnv($this->env);
	}
	
	private function getStorefronts()
	{
		return $this->storefront_service->getStorefronts();
	}
	
	private function getCategories()
	{
		$result = $this->category_model->select('id, left_key, right_key, parent_id, depth, name')
			->order('left_key asc')
			->query();
		$categories = array();
		$path_categories = array();
		
		foreach ($result as $i => $category)
		{
			while (count($path_categories) != 0)
			{
				$_category = end($path_categories);
				
				if ($category['left_key'] > $_category['left_key'] && $category['left_key'] < $_category['right_key'])
				{
					break;
				}
				
				array_pop($path_categories);
			}
			
			$_path_categories = array();
			
			foreach ($path_categories as $path_category)
			{
				$_path_categories[] = $path_category['name'];
			}
			
			$categories[$i] = $category;
			$categories[$i]['path'] = implode(' - ', $_path_categories);
			array_push($path_categories, $category);
		}
		
		return $categories;
	}
	
	private function getPluginSettings()
	{
		$settings = $this->plugin_settings_service->getSettings();
		
		return $this->settings_array_mapper->mapSettings($settings);
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
		
		foreach ($services as $name => $service)
		{
			$fields = $service->getFields();
			
			$result[$name] = $this->field_array_mapper->mapFields($fields);
		}
		
		return $result;
	}
	
	private function getGroupsStorefronts($loaded_groups_ids, $fields)
	{
		$groups_storefronts = $this->group_storefront_service->getAll();
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->group_storefront_service->loadRule($group_storefront);
			
			if (in_array($group_storefront->getId(), $loaded_groups_ids))
			{
				$this->group_storefront_service->loadSettings($group_storefront);
				$this->group_storefront_service->loadFieldsValues($group_storefront, $fields);
			}
		}
		
		return $this->group_storefront_array_mapper->mapGroupsStorefronts($groups_storefronts);
	}
	
	private function getGroupsCategories($loaded_groups_ids, $fields)
	{
		$groups_categories = $this->group_category_service->getAll();
		
		foreach ($groups_categories as $group_category)
		{
			$this->group_category_service->loadRules($group_category);
			
			if (in_array($group_category->getId(), $loaded_groups_ids))
			{
				$this->group_category_service->loadSettings($group_category);
				$this->group_category_service->loadFieldsValues($group_category, $fields);
			}
		}
		
		return $this->group_category_array_mapper->mapGroupsCategories($groups_categories);
	}
	
	private function getGeneralSettings()
	{
		$settings = $this->storefront_settings_service->getGeneralSettings();
		
		return $this->settings_array_mapper->mapSettings($settings);
	}
	
	private function getCustomVariables()
	{
		$custom_variables = array();
		
		foreach (array(
			'home',
			'category',
			'category_pagination',
			'product',
			'product_review',
			'product_page',
			'page',
			'tag',
			'brand',
			'brand_category',
			'group_category_data',
			'group_category_pagination',
			'group_category_subcategory',
			'group_category_subcategory_pagination',
			'group_category_product',
			'group_category_product_review',
			'group_category_product_review_page',
		) as $_type)
		{
			if (!isset($custom_variables[$_type]))
			{
				$custom_variables[$_type] = array();
			}
			
			$params_type = array(
				'type' => 'main',
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