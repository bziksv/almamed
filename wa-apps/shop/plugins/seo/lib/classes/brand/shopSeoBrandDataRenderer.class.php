<?php


class shopSeoBrandDataRenderer
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
	
	public function render($storefront, $page, $template)
	{
		$view = $this->getViewBuffer($storefront, $page);
		
		return $view->render($template);
	}
	
	public function renderAll($storefront, $page, $templates)
	{
		$view = $this->getViewBuffer($storefront, $page);
		
		return $view->renderAll($templates);
	}
	
	private function getViewBuffer($storefront, $page)
	{
		$view_buffer = $this->view_buffer_factory->createViewBuffer();
		$this->storefront_view_buffer_modifier->modify($storefront, $view_buffer);
		$this->pagination_view_buffer_modifier->modify($page, $view_buffer);
		$this->customs_view_buffer_modifier->modify($view_buffer);
		
		return $view_buffer;
	}
}