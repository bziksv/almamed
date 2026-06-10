<?php


class shopSeoCategoryViewBufferModifier
{
	private $category_data_source;
	private $category_inner_extender;
	
	public function __construct(
		shopSeoCategoryDataSource $category_data_source,
		shopSeoCategoryInnerExtender $category_inner_extender
	) {
		$this->category_data_source = $category_data_source;
		$this->category_inner_extender = $category_inner_extender;
	}
	
	public function modify($storefront, $category_id, shopSeoViewBuffer $view_buffer)
	{
		$category = $this->category_data_source->getCategoryData($category_id);
		
		$vars = array();
		$vars['category'] = $this->category_inner_extender->extend($storefront, $category, true);
		$vars['parent_categories'] = $vars['category']['parents'];
		$vars['root_category'] = reset($vars['category']['parents']);
		$vars['parent_category'] = end($vars['category']['parents']);
		$vars['parent_categories_names'] = array();
		
		foreach ($vars['category']['parents'] as $parent)
		{
			$vars['parent_categories_names'][] = $parent['name'];
		}
		
		$view_buffer->assign($vars);
	}
}