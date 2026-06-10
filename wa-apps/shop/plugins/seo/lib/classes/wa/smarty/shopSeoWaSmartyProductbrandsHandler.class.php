<?php


class shopSeoWaSmartyProductbrandsHandler
{
	private $request_handler_storage;
	private $storefront_source;
	
	public function __construct()
	{
		$this->request_handler_storage = shopSeoContext::getInstance()->getRequestHandlerStorage();
		$this->storefront_source = shopSeoContext::getInstance()->getStorefrontSource();
	}
	
	public function handle(
		/** @noinspection PhpUnusedParameterInspection */
		$_, Smarty_Internal_Template $smarty)
	{
		/** @var array $brand */
		$brand = wa()->getView()->getVars('brand');
		/** @var array $category */
		$category = wa()->getView()->getVars('category');
		$is_category = isset($category);

		if ($is_category)
		{
			$this->handleBrandCategory($brand['id'], $category['id']);
		}
		else
		{
			$this->handleBrand($brand['id']);
		}
		
		$this->request_handler_storage->applyInner();
		$smarty->assign(wa()->getView()->getVars());
	}

	private function handleBrand($brand_id)
	{
		$handler = new shopSeoWaBrandRequestHandler(
			$this->storefront_source->getCurrentStorefront(),
			$brand_id,
			waRequest::get('page', 1),
			waRequest::get('sort'),
			waRequest::get('order')
		);
		$this->request_handler_storage->setHandler($handler);
	}

	private function handleBrandCategory($brand_id, $category_id)
	{
		$handler = new shopSeoWaBrandCategoryRequestHandler(
			$this->storefront_source->getCurrentStorefront(),
			$brand_id,
			$category_id,
			waRequest::get('page', 1),
			waRequest::get('sort'),
			waRequest::get('order')
		);
		$this->request_handler_storage->setHandler($handler);
	}
}