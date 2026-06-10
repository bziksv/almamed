<?php

class shopSeoPlugin extends shopPlugin
{
	private static $is_init = false;
	private $request_handler_service;
	private $storefront_service;
	private $plugin_settings_service;
	private $env;
	private $wa_backend_category_dialog;
	private $wa_product_edit;

	public function __construct($info)
	{
		parent::__construct($info);
		$this->request_handler_service = shopSeoContext::getInstance()->getRequestHandlerStorage();
		$this->storefront_service = shopSeoContext::getInstance()->getStorefrontService();
		$this->plugin_settings_service = shopSeoContext::getInstance()->getPluginSettingsService();
		$this->env = shopSeoContext::getInstance()->getEnv();
		$this->wa_backend_category_dialog = shopSeoContext::getInstance()->getWaBackendCategoryDialog();
		$this->wa_product_edit = shopSeoContext::getInstance()->getWaProductEdit();
		self::$is_init = true;
	}

	public static function getPath($path)
	{
		return wa()->getAppPath('plugins/seo/' . $path, 'shop');
	}

	public static function isEnabled()
	{
		if (!self::$is_init)
		{
			try
			{
				wa('shop')->getPlugin('seo');
			}
			catch (waException $ignored)
			{
			}
		}

		$plugin_info = wa('shop')->getConfig()->getPluginInfo('seo');
		$is_enabled = $plugin_info !== array();

		return $is_enabled && shopSeoContext::getInstance()->getPluginSettingsService()->getSettings()->is_enabled;
	}

	public function routing($route = array())
	{
		if (!self::isEnabled())
		{
			return;
		}

		if (!$this->env->isEnabledProductbrands())
		{
			return;
		}

		$lazy_routing = new shopSeoWaLazyRouting();
		$params = $lazy_routing->getParamsByRouteAndPluginId($route, 'productbrands');
		$is_productbrands_action = ifset($params['app']) == 'shop'
			&& ifset($params['module']) == 'frontend'
			&& ifset($params['plugin']) == 'productbrands';

		if (!$is_productbrands_action)
		{
			return;
		}

		try
		{
			$handler = new shopSeoWaSmartyProductbrandsHandler();

			wa()->getView()->smarty->registerPlugin(
				'function',
				'shop_seo_productbrands_hook',
				array($handler, 'handle')
			);
		}
		catch (SmartyException $ignored)
		{

		}
	}

	public function frontendHead()
	{
		if (!self::isEnabled())
		{
			return;
		}

		$this->request_handler_service->applyOuter();
	}

	public function frontendHomepage()
	{
		if (!self::isEnabled())
		{
			return;
		}

		$handler = new shopSeoWaHomeRequestHandler($this->storefront_service->getCurrentStorefront());
		$this->request_handler_service->setHandler($handler);
		$this->request_handler_service->applyInner();
	}

	public function frontendCategory($category)
	{
		if (!self::isEnabled())
		{
			return;
		}

		$handler = new shopSeoWaCategoryRequestHandler(
			$this->storefront_service->getCurrentStorefront(),
			$category['id'],
			waRequest::get('page', 1),
			waRequest::get('sort'),
			waRequest::get('order')
		);
		$this->request_handler_service->setHandler($handler);
		$this->request_handler_service->applyInner();
	}

	public function frontendProduct($product)
	{
		if (!self::isEnabled())
		{
			return;
		}

		$is_review = waRequest::param('action') == 'productReviews';
		$is_page = waRequest::param('action') == 'productPage' || !!waRequest::param('page_url');

		if ($is_review)
		{
			if (!$this->plugin_settings_service->getSettings()->product_review_is_enabled)
			{
				return;
			}

			$handler = new shopSeoWaProductReviewRequestHandler(
				$this->storefront_service->getCurrentStorefront(), $product['id']
			);
			$this->request_handler_service->setHandler($handler);
		}
		elseif ($is_page)
		{
			if (!$this->plugin_settings_service->getSettings()->product_page_is_enabled)
			{
				return;
			}

			/** @var array $page */
			$page = wa()->getView()->getVars('page');

			$handler = new shopSeoWaProductPageRequestHandler(
				$this->storefront_service->getCurrentStorefront(), $product['id'], $page['id']
			);
			$this->request_handler_service->setHandler($handler);
		}
		else
		{
			$handler = new shopSeoWaProductRequestHandler(
				$this->storefront_service->getCurrentStorefront(), $product['id']
			);
			$this->request_handler_service->setHandler($handler);
		}

		$this->request_handler_service->applyInner();
	}

	public function frontendNav()
	{
		if (!self::isEnabled())
		{
			return;
		}

		if (!shopSeoContext::getInstance()->getRouting()->isPageAction())
		{
			return;
		}

		$page_id = waRequest::param('page_id');
		$handler = new shopSeoWaPageRequestHandler($this->storefront_service->getCurrentStorefront(), $page_id);
		$this->request_handler_service->setHandler($handler);
		$this->request_handler_service->applyInner();
	}

	public function frontendSearch()
	{
		if (!self::isEnabled())
		{
			return;
		}

		if (waRequest::param('action') != 'tag')
		{
			return;
		}

		$tag_name = waRequest::param('tag');
		$handler = new shopSeoWaTagRequestHandler(
			$this->storefront_service->getCurrentStorefront(),
			$tag_name,
			waRequest::get('page', 1),
			waRequest::get('sort'),
			waRequest::get('order')
		);
		$this->request_handler_service->setHandler($handler);
		$this->request_handler_service->applyInner();
	}

	public function backendCategoryDialog($category)
	{
		$state = $this->wa_backend_category_dialog->getState($category['id']);

		return $this->wa_backend_category_dialog->render($state);
	}

	public function backendProductEdit($product)
	{
		$state = $this->wa_product_edit->getState($product['id']);

		return array('basics' => $this->wa_product_edit->render($state));
	}

	public function categorySave($category)
	{
		$state_json = waRequest::request('seo_state');

		if (!$state_json)
		{
			return;
		}

		$state = json_decode($state_json, true);
		$this->wa_backend_category_dialog->save($category['id'], $state);
	}

	public function productSave($params)
	{
		$state_json = waRequest::request('seo_state');

		if (!$state_json)
		{
			return;
		}

		$state = json_decode($state_json, true);
		$this->wa_product_edit->save($params['data']['id'], $state);
	}
}
