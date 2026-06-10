<?php


class shopSeoProductDataRenderer
{
	private $storefront_view_buffer_modifier;
	private $product_view_buffer_modifier;
	private $view_buffer_factory;
	private $customs_view_buffer_modifier;
	
	public function __construct(
		shopSeoStorefrontViewBufferModifier $storefront_view_buffer_modifier,
		shopSeoProductViewBufferModifier $product_view_buffer_modifier,
		shopSeoViewBufferFactory $view_buffer_factory,
		shopSeoCustomsViewBufferModifier $customs_view_buffer_modifier
	) {
		$this->storefront_view_buffer_modifier = $storefront_view_buffer_modifier;
		$this->product_view_buffer_modifier = $product_view_buffer_modifier;
		$this->view_buffer_factory = $view_buffer_factory;
		$this->customs_view_buffer_modifier = $customs_view_buffer_modifier;
	}
	
	public function render($storefront, $product_id, $template)
	{
		$view = $this->getViewBuffer($storefront, $product_id);
		
		return $view->render($template);
	}
	
	public function renderAll($storefront, $product_id, $templates)
	{
		$view = $this->getViewBuffer($storefront, $product_id);
		
		return $view->renderAll($templates);
	}
	
	private function getViewBuffer($storefront, $product_id)
	{
		$view_buffer = $this->view_buffer_factory->createViewBuffer();
		$this->storefront_view_buffer_modifier->modify($storefront, $view_buffer);
		$this->product_view_buffer_modifier->modify($storefront, $product_id, $view_buffer);
		$this->customs_view_buffer_modifier->modify($view_buffer);
		
		return $view_buffer;
	}
}