<?php


class shopSeoTagDataRenderer
{
	private $view_buffer_factory;
	private $storefront_view_buffer_modifier;
	private $pagination_view_buffer_modifier;
	private $customs_view_buffer_modifier;
	
	public function __construct(
		shopSeoViewBufferFactory $view_buffer_factory,
		shopSeoStorefrontViewBufferModifier $storefront_view_buffer_modifier,
		shopSeoPaginationViewBufferModifier $pagination_view_buffer_modifier,
		shopSeoCustomsViewBufferModifier $customs_view_buffer_modifier
	) {
		$this->view_buffer_factory = $view_buffer_factory;
		$this->storefront_view_buffer_modifier = $storefront_view_buffer_modifier;
		$this->pagination_view_buffer_modifier = $pagination_view_buffer_modifier;
		$this->customs_view_buffer_modifier = $customs_view_buffer_modifier;
	}
	
	public function render($storefront, $tag_name, $page, $template)
	{
		$view = $this->getViewBuffer($storefront, $tag_name, $page);
		
		return $view->render($template);
	}
	
	public function renderAll($storefront, $tag_name, $page, $templates)
	{
		$view = $this->getViewBuffer($storefront, $tag_name, $page);
		
		return $view->renderAll($templates);
	}
	
	/**
	 * @param $storefront
	 * @param $tag_name
	 * @param $page
	 * @return shopSeoViewBuffer
	 */
	private function getViewBuffer($storefront, $tag_name, $page)
	{
		$view_buffer = $this->view_buffer_factory->createViewBuffer();
		$this->storefront_view_buffer_modifier->modify($storefront, $view_buffer);
		$this->pagination_view_buffer_modifier->modify($page, $view_buffer);
		$this->customs_view_buffer_modifier->modify($view_buffer);
		
		$vars = array();
		$vars['tag'] = array(
			'name' => $tag_name,
		);
		
		$view_buffer->assign($vars);
		
		return $view_buffer;
	}
}