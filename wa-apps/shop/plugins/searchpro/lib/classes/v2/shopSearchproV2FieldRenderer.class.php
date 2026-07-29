<?php

class shopSearchproV2FieldRenderer
{
	private static $helper_history_template;

	private $frontend;
	private $settings;
	private $env;

	public function __construct(shopSearchproFrontend $frontend)
	{
		$this->frontend = $frontend;
		$this->settings = shopSearchproV2Settings::create();
		$this->env = $this->settings->getEnv();
	}

	public function render($params = array())
	{
		if (!$this->settings->isEnabled()) {
			return '';
		}

		$assets = array();
		if (empty($params['no_css'])) {
			$css = array('field');
			if ($this->settings->get('design_custom_fonts_status')) {
				$css[] = 'fonts';
			}
			$assets = array(
				'js' => array('field-v2'),
				'css' => $css,
			);
		} else {
			$assets = array(
				'js' => array('field-v2'),
				'css' => array(),
			);
		}

		$uniqid = uniqid('searchpro-field-wrapper-');
		$query = waRequest::param('query') ? waRequest::param('query') : '';

		$shell_vars = array(
			'query' => $query,
			'placeholder' => $this->settings->get('placeholder'),
			'category_filter_status' => (bool) $this->settings->get('category_filter_status'),
			'selected_category_id' => (int) waRequest::param('category_id', 0),
			'selected_category_name' => '',
		);

		$assets_links = $this->frontend->getAssets($assets);
		$vars = array(
			'uniqid' => $uniqid,
			'field_shell' => $this->frontend->fetchTemplate(
				wa()->getAppPath('plugins/searchpro/templates/actions/frontend/FrontendFieldShell.html', 'shop'),
				$shell_vars
			),
			'searchpro_config' => $this->buildRuntimeConfig(),
			'params' => $this->buildFieldParams(),
			'asset_urls' => array(
				'field_v2' => ifset($assets_links, 'js', 'field-v2', 'url', ''),
			),
		);

		return $this->frontend->outputFrontendV2(
			wa()->getAppPath('plugins/searchpro/templates/actions/frontend/FrontendFieldV2.html', 'shop'),
			$assets,
			$vars,
			array(
				'class' => 'js-searchpro__field-wrapper',
				'id' => $uniqid,
			)
		);
	}

	private function buildRuntimeConfig()
	{
		$plugin = shopSearchproPlugin::getInstance();
		$suggest_url = $this->env->getRouteUrl('shop/frontend/suggest', array('plugin' => 'searchpro'), true);

		return array(
			'plugin_url' => $plugin->getPluginStaticUrl(true),
			'dropdown_url' => $suggest_url,
			'suggest_url' => $suggest_url,
			'results_url' => $this->env->getRouteUrl('shop/frontend/page', array('plugin' => 'searchpro'), true),
			'cart_add_url' => $this->env->getRouteUrl('shop/frontendCart/add'),
			'version' => $plugin->getVersion(),
		);
	}

	private function buildFieldParams()
	{
		$popular_status = (bool) $this->settings->get('dropdown_popular_is_visible');
		$history_status = (bool) $this->settings->get('dropdown_history_is_visible');

		$params = array(
			'dropdown_status' => (bool) $this->settings->get('dropdown_status'),
			'category_status' => (bool) $this->settings->get('category_filter_status'),
			'category_filter_deep' => (int) $this->settings->get('category_filter_deep', 1),
			'dropdown_min_length' => (int) $this->settings->get('dropdown_min_length'),
			'history_cookie_key' => shopSearchproEnv::HISTORY_COOKIE_KEY,
			'popular_status' => $popular_status,
			'popular_max_count' => (int) $this->settings->get('dropdown_popular_max_count'),
			'history_status' => $history_status,
			'history_search_status' => (bool) $this->settings->get('dropdown_history_status'),
			'history_max_count' => (int) $this->settings->get('dropdown_history_max_count'),
			'clear_button_status' => (bool) $this->settings->get('clear_button'),
			'current_category_id' => (int) waRequest::param('category_id', 0),
			'categories_url' => $this->env->getRouteUrl('shop/frontend/categories', array('plugin' => 'searchpro'), true),
			'popular_url' => $this->env->getRouteUrl('shop/frontend/popular', array('plugin' => 'searchpro'), true),
			'helper_url' => $this->env->getRouteUrl('shop/frontend/helper', array('plugin' => 'searchpro'), true),
		);

		if ($history_status || $popular_status) {
			$params['helper_dropdown'] = array(
				'current' => '',
				'template' => self::getHelperHistoryTemplate(
					$this->env->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro'), true)
				),
			);
		}

		return $params;
	}

	private static function getHelperHistoryTemplate($results_url)
	{
		if (self::$helper_history_template !== null) {
			return self::$helper_history_template;
		}

		$href = htmlspecialchars($results_url . '/%QUERY%/', ENT_QUOTES, 'UTF-8');
		self::$helper_history_template = <<<HTML
<div class="searchpro__dropdown">
	<div class="searchpro__dropdown-group searchpro__dropdown-group-history">
		<div class="js-searchpro__dropdown-history">
			<div class="searchpro__dropdown-group-title">История запросов</div>
			<div class="searchpro__dropdown-group-entities js-searchpro__dropdown-entities">
				<a class="searchpro__dropdown-entity js-searchpro__dropdown-entity"
				   data-action="value:data-value" data-value="" href="{$href}">
					<span class="js-searchpro__dropdown-entity_query"></span>
				</a>
			</div>
		</div>
	</div>
</div>
HTML;

		return self::$helper_history_template;
	}
}
