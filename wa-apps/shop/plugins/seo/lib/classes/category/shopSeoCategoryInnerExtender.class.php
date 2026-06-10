<?php


class shopSeoCategoryInnerExtender
{
	private $category_data_source;
	private $category_data_collector;
	
	public function __construct(
		shopSeoCategoryDataSource $category_data_source,
		shopSeoCategoryDataCollector $category_data_collector
	) {
		$this->category_data_source = $category_data_source;
		$this->category_data_collector = $category_data_collector;
	}
	
	public function extend($storefront, $category, $include_parent_categories)
	{
		if ($include_parent_categories)
		{
			$path_categories = $this->category_data_source->getCategoryPath($category['id']);
			$parent_categories = array_reverse($path_categories);
			
			foreach ($parent_categories as $i => $parent_category)
			{
				$parent_categories[$i] = $this->extend(
					$storefront,
					$parent_category,
					false
				);
			}
			
			$category['parents'] = $parent_categories;
		}
		
		$seo_name = $this->category_data_collector->collectSeoName($storefront, $category['id'], $info);
		$fields = $this->category_data_collector->collectFieldsValues($storefront, $category['id'], $info);
		
		if ($seo_name === '')
		{
			$seo_name = $category['name'];
		}
		
		$category['seo_name'] = $seo_name;
		$category['fields'] = $fields;
		
		$category = array_merge(
			$category,
			$this->category_data_source->getCategoryProductsData($storefront, $category['id'])
		);

		if (!array_key_exists('params', $category) || !is_array($category['params']))
		{
			$category['params'] = $this->category_data_source->getCategoryParams($category['id']);
		}
		
		return $category;
	}
}