<?php


class shopSeoWaViewBufferFactory implements shopSeoViewBufferFactory
{
	private $storefront_data_collector;
	
	public function __construct(shopSeoStorefrontDataCollector $storefront_data_collector)
	{
		$this->storefront_data_collector = $storefront_data_collector;
	}
	
	public function createViewBuffer()
	{
		$view_buffer = new shopSeoWaViewBuffer();
		$view_buffer->assign(wa()->getView()->getVars());
		
		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();
		$vars['host'] = waRequest::server('HTTP_HOST');
		$vars['store_info'] = array(
			'name' => $config->getGeneralSettings('name'),
			'phone' => $config->getGeneralSettings('phone'),
		);
		
		$view_buffer->assign($vars);
		
		return $view_buffer;
	}
}