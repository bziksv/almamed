<?php


class shopSeoCategoryDataCollector
{
	private $group_storefront_service;
	private $group_category_service;
	private $category_settings_service;
	private $category_data_source;
	private $plugin_settings_service;
	private $category_field_service;
	private $category_fields_values_service;
	private $group_category_fields_values_service;
	private $storefront_settings_service;
	private $env;
	
	public function __construct(
		shopSeoGroupStorefrontService $group_storefront_service,
		shopSeoGroupCategoryService $group_category_service,
		shopSeoCategorySettingsService $category_settings_service,
		shopSeoCategoryDataSource $category_data_source,
		shopSeoPluginSettingsService $plugin_settings_service,
		shopSeoCategoryFieldService $category_field_service,
		shopSeoCategoryFieldsValuesService $category_fields_values_service,
		shopSeoGroupCategoryFieldsValuesService $group_category_fields_values_service,
		shopSeoStorefrontSettingsService $storefront_settings_service,
		shopSeoEnv $env
	) {
		$this->group_storefront_service = $group_storefront_service;
		$this->group_category_service = $group_category_service;
		$this->category_settings_service = $category_settings_service;
		$this->category_data_source = $category_data_source;
		$this->plugin_settings_service = $plugin_settings_service;
		$this->category_field_service = $category_field_service;
		$this->category_fields_values_service = $category_fields_values_service;
		$this->group_category_fields_values_service = $group_category_fields_values_service;
		$this->storefront_settings_service = $storefront_settings_service;
		$this->env = $env;
	}
	
	public function collect($storefront, $category_id, $is_pagination, &$info)
	{
		$keys = array(
			'meta_title',
			'h1',
			'meta_description',
			'meta_keywords',
			'description',
			'additional_description',
		);
		
		if ($this->env->isSupportOg())
		{
			$keys[] = 'og:title';
			$keys[] = 'og:description';
		}
		
		$collection = new shopSeoLayoutsCollection($keys);
		
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		$groups_categories = $this->getGroupsCategories($storefront, $category_id);
		
		if ($is_pagination)
		{
			foreach ($groups_storefronts as $group_storefront)
			{
				$this->collectPersonalStorefrontPagination($group_storefront, $category_id, $collection);
			}
			
			$this->collectPersonalGeneralPagination($category_id, $collection);
		}
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectPersonalStorefront($group_storefront, $category_id, $collection);
		}
		
		$this->collectPersonalGeneral($category_id, $collection);
		
		$this->collectPersonal($category_id, $collection);
		
		if ($is_pagination)
		{
			foreach ($groups_categories as $group_category)
			{
				$this->collectGroupCategoryPagination($group_category, $collection);
			}
		}
		
		foreach ($groups_categories as $group_category)
		{
			$this->collectGroupCategory($group_category, $collection);
		}
		
		$path_categories = $this->category_data_source->getCategoryPath($category_id);
		
		if ($is_pagination)
		{
			foreach ($path_categories as $path_category)
			{
				foreach ($groups_storefronts as $groups_storefront)
				{
					$this->collectStorefrontSubcategoryPagination($groups_storefront, $path_category, $collection);
				}
				$this->collectGeneralSubcategoryPagination($path_category, $collection);

				$path_groups_categories = $this->getGroupsCategories($storefront, $path_category['id']);

				foreach ($path_groups_categories as $path_group_category)
				{
					$this->collectGroupCategorySubcategoryPagination($path_category, $path_group_category, $collection);
				}
			}
			
			foreach ($groups_storefronts as $group_storefront)
			{
				$this->collectGroupStorefrontPagination($group_storefront, $collection);
			}
			
			$this->collectGeneralPagination($collection);
		}
		
		foreach ($path_categories as $path_category)
		{
			foreach ($groups_storefronts as $groups_storefront)
			{
				$this->collectStorefrontSubcategory($groups_storefront, $path_category, $collection);
			}
			$this->collectGeneralSubcategory($path_category, $collection);

			$path_groups_categories = $this->getGroupsCategories($storefront, $path_category['id']);
			
			foreach ($path_groups_categories as $path_group_category)
			{
				$this->collectGroupCategorySubcategory($path_category, $path_group_category, $collection);
			}
		}
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$this->collectGroupStorefront($group_storefront, $collection);
		}
		
		$this->collectGeneral($collection);
		
		$info = $collection->getInfo();
		
		return $collection->getResult();
	}
	
	public function collectSeoName($storefront, $category_id, &$info)
	{
		$collection = new shopSeoLayoutsCollection(array(
			'seo_name',
		));
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$category_settings = $this->getStorefrontCategorySettings($group_storefront->getId(), $category_id);
			
			$collection->push(array(
				'seo_name' => $category_settings->seo_name,
			), 1, "personal; storefront group: \"{$group_storefront->getName()}\"");
		}
		
		$general_category_settings = $this->getGeneralCategorySettings($category_id);
		
		$collection->push(array(
			'seo_name' => $general_category_settings->seo_name,
		), 1, "personal; general");
		
		$result = $collection->getResult();
		
		$info = $collection->getInfo();
		
		return $result['seo_name'];
	}
	
	public function collectFieldsValues($storefront, $category_id, &$info)
	{
		$fields = $this->category_field_service->getFields();
		$fields_ids = array();
		
		foreach ($fields as $field)
		{
			$fields_ids[] = $field->getId();
		}
		
		$collection = new shopSeoLayoutsCollection($fields_ids);
		
		$groups_storefronts = $this->getGroupsStorefronts($storefront);
		$groups_categories = $this->getGroupsCategories($storefront, $category_id);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$fields_values = $this->category_fields_values_service->getByGroupStorefrontIdAndCategoryIdAndFields($group_storefront->getId(), $category_id, $fields);
			$values = $fields_values->getValues();
			
			foreach ($fields_values->getFields() as $i => $field)
			{
				$collection->push(array(
					$field->getId() => $values[$i]
				), 1, "personal; group storefront: \"{$group_storefront->getName()}\"");
			}
		}
		
		$general_fields_values = $this->category_fields_values_service->getGeneralByCategoryIdAndFields($category_id, $fields);
		$values = $general_fields_values->getValues();
		
		foreach ($general_fields_values->getFields() as $i => $field)
		{
			$collection->push(array(
				$field->getId() => $values[$i]
			), 1, "personal; general");
		}
		
		foreach ($groups_categories as $group_category)
		{
			$fields_values = $this->group_category_fields_values_service->getByGroupIdAndFields($group_category->getId(), $fields);
			$values = $fields_values->getValues();
			
			foreach ($fields_values->getFields() as $i => $field)
			{
				$collection->push(array(
					$field->getId() => $values[$i]
				), 1, "personal; group category: \"{$group_category->getName()}\"");
			}
		}
		
		$info = $collection->getInfo();
		
		$fields_values = $collection->getResult();
		$result = array();
		
		foreach ($fields as $field)
		{
			$result[$field->getId()] = array(
				'name' => $field->getName(),
				'value' => $fields_values[$field->getId()],
			);
		}
		
		return $result;
	}
	
	/**
	 * @param $category_id
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectPersonal($category_id, shopSeoLayoutsCollection $collection)
	{
		$category = $this->category_data_source->getCategoryData($category_id);
		
		$collection->push(array(
			'meta_title' => $category['meta_title'],
			'meta_keywords' => $category['meta_keywords'],
			'meta_description' => $category['meta_description'],
			'description' => $category['description'],
		), 0, 'personal');
		
		if ($this->env->isSupportOg())
		{
			$collection->push(array(
				'og:title' => $category['og']['title'],
				'og:description' => $category['og']['description'],
			), 0, 'personal');
		}
	}
	
	/**
	 * @param shopSeoGroupStorefront $group_storefront
	 * @param $category_id
	 * @param shopSeoLayoutsCollection $collection
	 * @return void
	 */
	private function collectPersonalStorefrontPagination(
		shopSeoGroupStorefront $group_storefront,
		$category_id,
		shopSeoLayoutsCollection $collection
	) {
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}
		
		$category_settings = $this->getStorefrontCategorySettings($group_storefront->getId(), $category_id);
		
		if ($category_settings->pagination_is_enabled)
		{
			$comment = "personal pagination; group storefront: \"{$group_storefront->getName()}\"";
			$collection->push(array(
				'meta_title' => $category_settings->pagination_meta_title,
				'meta_description' => $category_settings->pagination_meta_description,
				'meta_keywords' => $category_settings->pagination_meta_keywords,
				'description' => $category_settings->pagination_description,
			), 0, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $category_settings->pagination_additional_description,
				), 0, $comment);
			}
			
			if ($this->getPluginSettings()->category_product_h1_is_enabled)
			{
				$collection->push(array(
					'h1' => $category_settings->pagination_h1,
				), 0, $comment);
			}
		}
	}
	
	/**
	 * @param $category_id
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectPersonalGeneralPagination(
		$category_id, shopSeoLayoutsCollection $collection
	) {
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}
		
		$general_category_settings = $this->getGeneralCategorySettings($category_id);
		
		if ($general_category_settings->pagination_is_enabled)
		{
			$comment = 'personal pagination; general';
			$collection->push(array(
				'meta_title' => $general_category_settings->pagination_meta_title,
				'meta_description' => $general_category_settings->pagination_meta_description,
				'meta_keywords' => $general_category_settings->pagination_meta_keywords,
				'description' => $general_category_settings->pagination_description,
			), 0, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $general_category_settings->pagination_additional_description,
				), 0, $comment);
			}
			
			if ($this->getPluginSettings()->category_product_h1_is_enabled)
			{
				$collection->push(array(
					'h1' => $general_category_settings->pagination_h1,
				), 0, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoGroupStorefront $group_storefront
	 * @param $category_id
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectPersonalStorefront(
		shopSeoGroupStorefront $group_storefront, $category_id, shopSeoLayoutsCollection $collection
	) {
		$category_settings = $this->getStorefrontCategorySettings($group_storefront->getId(), $category_id);
		
		$comment = "personal; group storefront: \"{$group_storefront->getName()}\"";
		$collection->push(array(
			'meta_title' => $category_settings->meta_title,
			'meta_description' => $category_settings->meta_description,
			'meta_keywords' => $category_settings->meta_keywords,
			'description' => $category_settings->description,
		), 0, $comment);
		
		if ($this->getPluginSettings()->category_additional_description_is_enabled)
		{
			$collection->push(array(
				'additional_description' => $category_settings->additional_description,
			), 0, $comment);
		}
		
		if ($this->getPluginSettings()->category_product_h1_is_enabled)
		{
			$collection->push(array(
				'h1' => $category_settings->h1,
			), 0, $comment);
		}
	}
	
	/**
	 * @param $category_id
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectPersonalGeneral(
		$category_id, shopSeoLayoutsCollection $collection
	) {
		$general_category_settings = $this->getGeneralCategorySettings($category_id);
		
		$comment = 'personal; general';
		
		if ($this->getPluginSettings()->category_additional_description_is_enabled)
		{
			$collection->push(array(
				'additional_description' => $general_category_settings->additional_description,
			), 0, $comment);
		}
		
		if ($this->getPluginSettings()->category_product_h1_is_enabled)
		{
			$collection->push(array(
				'h1' => $general_category_settings->h1,
			), 0, $comment);
		}
	}
	
	/**
	 * @param shopSeoGroupStorefront $group_storefront
	 * @param shopSeoLayoutsCollection $collection
	 * @return void
	 */
	private function collectGroupStorefrontPagination(
		shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection
	) {
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}
		
		$storefront_settings = $group_storefront->getSettings();
		
		if ($storefront_settings->category_is_enabled && $storefront_settings->category_pagination_is_enabled)
		{
			$comment = "pagination; group storefront: \"{$group_storefront->getName()}\"";
			$collection->push(array(
				'h1' => $storefront_settings->category_pagination_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $storefront_settings->category_pagination_meta_title,
				'meta_description' => $storefront_settings->category_pagination_meta_description,
				'meta_keywords' => $storefront_settings->category_pagination_meta_keywords,
			), $storefront_settings->category_pagination_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $storefront_settings->category_pagination_description,
			), $storefront_settings->category_pagination_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $storefront_settings->category_pagination_additional_description,
				), 1, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGeneralPagination(
		shopSeoLayoutsCollection $collection
	) {
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}
		
		$general_storefront_settings = $this->getGeneralStorefrontSettings();
		
		if ($general_storefront_settings->category_is_enabled
			&& $general_storefront_settings->category_pagination_is_enabled)
		{
			$comment = 'pagination; general';
			$collection->push(array(
				'h1' => $general_storefront_settings->category_pagination_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $general_storefront_settings->category_pagination_meta_title,
				'meta_description' => $general_storefront_settings->category_pagination_meta_description,
				'meta_keywords' => $general_storefront_settings->category_pagination_meta_keywords,
			), $general_storefront_settings->category_pagination_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $general_storefront_settings->category_pagination_description,
			), $general_storefront_settings->category_pagination_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $general_storefront_settings->category_pagination_additional_description,
				), 1, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoGroupStorefront $group_storefront
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGroupStorefront(
		shopSeoGroupStorefront $group_storefront, shopSeoLayoutsCollection $collection
	) {
		$storefront_settings = $group_storefront->getSettings();
		
		if ($storefront_settings->category_is_enabled)
		{
			$comment = "group storefront: \"{$group_storefront->getName()}\"";
			$collection->push(array(
				'h1' => $storefront_settings->category_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $storefront_settings->category_meta_title,
				'meta_description' => $storefront_settings->category_meta_description,
				'meta_keywords' => $storefront_settings->category_meta_keywords,
			), $storefront_settings->category_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $storefront_settings->category_description,
			), $storefront_settings->category_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $storefront_settings->category_additional_description,
				), 1, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGeneral(
		shopSeoLayoutsCollection $collection
	) {
		$general_storefront_settings = $this->getGeneralStorefrontSettings();
		
		if ($general_storefront_settings->category_is_enabled)
		{
			$comment = 'general';
			$collection->push(array(
				'h1' => $general_storefront_settings->category_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $general_storefront_settings->category_meta_title,
				'meta_description' => $general_storefront_settings->category_meta_description,
				'meta_keywords' => $general_storefront_settings->category_meta_keywords,
			), $general_storefront_settings->category_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $general_storefront_settings->category_description,
			), $general_storefront_settings->category_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $general_storefront_settings->category_additional_description,
				), 1, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoGroupCategory $group_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGroupCategoryPagination(
		shopSeoGroupCategory $group_category, shopSeoLayoutsCollection $collection
	) {
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}
		
		$category_settings = $group_category->getSettings();
		
		if ($category_settings->pagination_is_enabled)
		{
			$comment = "pagination; group category: \"{$group_category->getName()}\"";
			$collection->push(array(
				'h1' => $category_settings->pagination_h1,
				'meta_title' => $category_settings->pagination_meta_title,
				'meta_description' => $category_settings->pagination_meta_description,
				'meta_keywords' => $category_settings->pagination_meta_keywords,
				'description' => $category_settings->pagination_description,
			), 0, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $category_settings->pagination_additional_description,
				), 0, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoGroupCategory $group_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGroupCategory(shopSeoGroupCategory $group_category, shopSeoLayoutsCollection $collection)
	{
		$category_settings = $group_category->getSettings();
		
		$comment = "group category: \"{$group_category->getName()}\"";
		
		if ($category_settings->data_is_enabled)
		{
			$collection->push(array(
				'h1' => $category_settings->h1,
				'meta_title' => $category_settings->meta_title,
				'meta_description' => $category_settings->meta_description,
				'meta_keywords' => $category_settings->meta_keywords,
				'description' => $category_settings->description,
			), 0, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $category_settings->additional_description,
				), 0, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoGroupStorefront $group_storefront
	 * @param $path_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectStorefrontSubcategoryPagination(shopSeoGroupStorefront $group_storefront, $path_category, shopSeoLayoutsCollection $collection)
	{
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}
		
		if (!$this->getPluginSettings()->category_subcategories_is_enabled)
		{
			return;
		}
		
		$category_settings = $this->getStorefrontCategorySettings($group_storefront->getId(), $path_category['id']);
		
		if ($category_settings->subcategory_is_enabled && $category_settings->subcategory_pagination_is_enabled)
		{
			$comment = "subcategory pagination; category: \"{$path_category['name']}\"";
			$collection->push(array(
				'h1' => $category_settings->subcategory_pagination_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $category_settings->subcategory_pagination_meta_title,
				'meta_description' => $category_settings->subcategory_pagination_meta_description,
				'meta_keywords' => $category_settings->subcategory_pagination_meta_keywords,
			), $category_settings->subcategory_pagination_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $category_settings->subcategory_pagination_description,
			), $category_settings->subcategory_pagination_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $category_settings->subcategory_pagination_additional_description,
				), 1, $comment);
			}
		}
	}

	/**
	 * @param $path_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGeneralSubcategoryPagination($path_category, shopSeoLayoutsCollection $collection)
	{
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}

		if (!$this->getPluginSettings()->category_subcategories_is_enabled)
		{
			return;
		}

		$category_settings = $this->getGeneralCategorySettings($path_category['id']);

		if ($category_settings->subcategory_is_enabled && $category_settings->subcategory_pagination_is_enabled)
		{
			$comment = "subcategory pagination; category: \"{$path_category['name']}\"";
			$collection->push(array(
				'h1' => $category_settings->subcategory_pagination_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $category_settings->subcategory_pagination_meta_title,
				'meta_description' => $category_settings->subcategory_pagination_meta_description,
				'meta_keywords' => $category_settings->subcategory_pagination_meta_keywords,
			), $category_settings->subcategory_pagination_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $category_settings->subcategory_pagination_description,
			), $category_settings->subcategory_pagination_ignore_description ? 2 : 1, $comment);

			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $category_settings->subcategory_pagination_additional_description,
				), 1, $comment);
			}
		}
	}
	
	/**
	 * @param $path_category
	 * @param shopSeoGroupCategory $path_group_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGroupCategorySubcategoryPagination(
		$path_category, shopSeoGroupCategory $path_group_category, shopSeoLayoutsCollection $collection
	) {
		if (!$this->getPluginSettings()->category_pagination_is_enabled)
		{
			return;
		}
		
		if (!$this->getPluginSettings()->category_subcategories_is_enabled)
		{
			return;
		}
		
		$path_category_settings = $path_group_category->getSettings();
		
		if ($path_category_settings->subcategory_is_enabled
			&& $path_category_settings->subcategory_pagination_is_enabled)
		{
			$comment = "subcategory pagination; category: \"{$path_category['name']}\"; group category \"{$path_group_category->getName()}\"";
			$collection->push(array(
				'h1' => $path_category_settings->subcategory_pagination_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $path_category_settings->subcategory_pagination_meta_title,
				'meta_description' => $path_category_settings->subcategory_pagination_meta_description,
				'meta_keywords' => $path_category_settings->subcategory_pagination_meta_keywords,
			), $path_category_settings->subcategory_pagination_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $path_category_settings->subcategory_pagination_description,
			), $path_category_settings->subcategory_pagination_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $path_category_settings->subcategory_pagination_additional_description,
				), 1, $comment);
			}
		}
	}
	
	/**
	 * @param shopSeoGroupStorefront $groups_storefront
	 * @param $path_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectStorefrontSubcategory(shopSeoGroupStorefront $groups_storefront, $path_category, shopSeoLayoutsCollection $collection)
	{
		if (!$this->getPluginSettings()->category_subcategories_is_enabled)
		{
			return;
		}
		
		$category_settings = $this->getStorefrontCategorySettings($groups_storefront->getId(), $path_category['id']);
		
		if ($category_settings->subcategory_is_enabled)
		{
			$comment = "subcategory; category: \"{$path_category['name']}\"";
			$collection->push(array(
				'h1' => $category_settings->subcategory_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $category_settings->subcategory_meta_title,
				'meta_description' => $category_settings->subcategory_meta_description,
				'meta_keywords' => $category_settings->subcategory_meta_keywords,
			), $category_settings->subcategory_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $category_settings->subcategory_description,
			), $category_settings->subcategory_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $category_settings->subcategory_additional_description,
				), 1, $comment);
			}
		}
	}

	/**
	 * @param $path_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGeneralSubcategory($path_category, shopSeoLayoutsCollection $collection)
	{
		if (!$this->getPluginSettings()->category_subcategories_is_enabled)
		{
			return;
		}

		$category_settings = $this->getGeneralCategorySettings($path_category['id']);

		if ($category_settings->subcategory_is_enabled)
		{
			$comment = "subcategory; category: \"{$path_category['name']}\"";
			$collection->push(array(
				'h1' => $category_settings->subcategory_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $category_settings->subcategory_meta_title,
				'meta_description' => $category_settings->subcategory_meta_description,
				'meta_keywords' => $category_settings->subcategory_meta_keywords,
			), $category_settings->subcategory_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $category_settings->subcategory_description,
			), $category_settings->subcategory_ignore_description ? 2 : 1, $comment);

			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $category_settings->subcategory_additional_description,
				), 1, $comment);
			}
		}
	}
	
	/**
	 * @param $path_category
	 * @param shopSeoGroupCategory $path_group_category
	 * @param shopSeoLayoutsCollection $collection
	 */
	private function collectGroupCategorySubcategory(
		$path_category, shopSeoGroupCategory $path_group_category, shopSeoLayoutsCollection $collection
	) {
		if (!$this->getPluginSettings()->category_subcategories_is_enabled)
		{
			return;
		}
		
		$path_category_settings = $path_group_category->getSettings();
		
		if ($path_category_settings->subcategory_is_enabled)
		{
			$comment = "subcategory; category: \"{$path_category['name']}\"; group category: \"{$path_group_category->getName()}\"";
			$collection->push(array(
				'h1' => $path_category_settings->subcategory_h1,
			), 1, $comment);
			$collection->push(array(
				'meta_title' => $path_category_settings->subcategory_meta_title,
				'meta_description' => $path_category_settings->subcategory_meta_description,
				'meta_keywords' => $path_category_settings->subcategory_meta_keywords,
			), $path_category_settings->subcategory_ignore_meta_data ? 2 : 1, $comment);
			$collection->push(array(
				'description' => $path_category_settings->subcategory_description,
			), $path_category_settings->subcategory_ignore_description ? 2 : 1, $comment);
			
			if ($this->getPluginSettings()->category_additional_description_is_enabled)
			{
				$collection->push(array(
					'additional_description' => $path_category_settings->subcategory_additional_description,
				), 1, $comment);
			}
		}
	}
	
	public function getPluginSettings()
	{
		return $this->plugin_settings_service->getSettings();
	}
	
	private function getGroupsStorefronts($storefront)
	{
		$groups_storefronts = $this->group_storefront_service->getByStorefront($storefront);
		
		foreach ($groups_storefronts as $group_storefront)
		{
			if (!$group_storefront->getSettings())
			{
				$this->group_storefront_service->loadSettings($group_storefront);
			}
		}
		
		return $groups_storefronts;
	}
	
	private function getGroupsCategories($storefront, $category_id)
	{
		$groups_categories = $this->group_category_service->getByStorefrontAndCategoryId($storefront, $category_id);
		
		foreach ($groups_categories as $group_category)
		{
			if (!$group_category->getSettings())
			{
				$this->group_category_service->loadSettings($group_category);
			}
		}
		
		return $groups_categories;
	}
	
	private function getStorefrontCategorySettings($group_storefront_id, $category_id)
	{
		return $this->category_settings_service->getByGroupStorefrontIdAndCategoryId(
			$group_storefront_id,
			$category_id
		);
	}
	
	private function getGeneralCategorySettings($category_id)
	{
		return $this->category_settings_service->getGeneralByCategoryId(
			$category_id
		);
	}
	
	private function getGeneralStorefrontSettings()
	{
		return $this->storefront_settings_service->getGeneralSettings();
	}
}
