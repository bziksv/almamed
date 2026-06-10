<?php


class shopSeoStorefrontDataRenderer
{
	private $view_buffer_factory;
	private $storefront_view_buffer_modifier;
	private $customs_view_buffer_modifier;
	
	public function __construct(
		shopSeoViewBufferFactory $view_buffer_factory,
		shopSeoStorefrontViewBufferModifier $storefront_view_buffer_modifier,
		shopSeoCustomsViewBufferModifier $customs_view_buffer_modifier
	) {
		$this->view_buffer_factory = $view_buffer_factory;
		$this->storefront_view_buffer_modifier = $storefront_view_buffer_modifier;
		$this->customs_view_buffer_modifier = $customs_view_buffer_modifier;
	}
	
	public function render($storefront, $template)
	{
		$view = $this->getViewBuffer($storefront);
		
		return $view->render($template);
	}
	
	public function renderAll($storefront, $templates)
	{
		$view = $this->getViewBuffer($storefront);
		
		return $view->renderAll($templates);
	}
	
	private function getViewBuffer($storefront)
	{
		$view_buffer = $this->view_buffer_factory->createViewBuffer();
		$this->storefront_view_buffer_modifier->modify($storefront, $view_buffer);
		$this->customs_view_buffer_modifier->modify($view_buffer);
		
		return $view_buffer;
	}
}