<?php

class shopSearchproV2PageService
{
	private $settings;
	private $pipeline;
	private $finder;

	public function __construct(shopSearchproV2Settings $settings = null)
	{
		$this->settings = $settings !== null ? $settings : shopSearchproV2Settings::create();
		$this->pipeline = new shopSearchproV2CorrectorPipeline();
	}

	public function build($query, $category_id = 0)
	{
		$query = shopSearchproPluginHelper::prepareQuery($query);
		$category_id = (int) $category_id;

		list($query, $products) = $this->findProductsWithLayoutFallback($query, $category_id);

		return new shopSearchproV2PageContext(
			$query,
			$category_id,
			$products,
			$products->isEmpty()
		);
	}

	/**
	 * @return array{0: string, 1: shopSearchproResult}
	 */
	public function findProductsWithLayoutFallback($query, $category_id = 0)
	{
		$category_id = (int) $category_id;
		$products = $this->findProducts($query, $category_id);

		if ($query !== '') {
			$typo_fixed = $this->tryTypoCandidates($query, $category_id, $products);
			if ($typo_fixed !== null) {
				return $typo_fixed;
			}
		}

		if (!$products->isEmpty() || $query === '') {
			return array($query, $products);
		}

		foreach (shopSearchproV2KeyboardLayoutHelper::candidates($query) as $corrected_query) {
			if ($corrected_query === $query) {
				continue;
			}
			$this->finder = null;
			$retry = $this->findProducts($corrected_query, $category_id);
			if (!$retry->isEmpty()) {
				return array($corrected_query, $retry);
			}
		}

		return array($query, $products);
	}

	/**
	 * @param shopSearchproResult|null $current
	 * @return array{0: string, 1: shopSearchproResult}|null
	 */
	private function tryTypoCandidates($query, $category_id, shopSearchproResult $current = null)
	{
		$initial = $current ? array_values($current->getInitial()) : array();
		if ($current && !$current->isEmpty() && !shopSearchproV2TypoHelper::isWeakMatch($query, $initial)) {
			return null;
		}

		foreach (shopSearchproV2TypoHelper::candidates($query, $this->settings) as $corrected_query) {
			$this->finder = null;
			$retry = $this->findProducts($corrected_query, $category_id);
			if ($retry->isEmpty()) {
				continue;
			}
			if (!shopSearchproV2TypoHelper::isWeakMatch($corrected_query, array_values($retry->getInitial()))) {
				return array($corrected_query, $retry);
			}
		}

		return null;
	}

	public function findProducts($query, $category_id = 0)
	{
		$finder = $this->getFinder($category_id);
		return $finder->find('products', $query);
	}

	public function getFinder($category_id = 0)
	{
		if ($this->finder === null) {
			$params = $this->pipeline->buildFinderParams(
				$this->settings,
				'page',
				$category_id,
				$this->settings->getEnv()
			);
			$this->finder = new shopSearchproFinder($params);
		}

		return $this->finder;
	}

	public function shouldBuildFilters(array $get_params = null)
	{
		if (!(bool) $this->settings->get('page_filter_status')) {
			return false;
		}

		if ($get_params === null) {
			$get_params = waRequest::get();
		}

		foreach (array('page', 'sort', 'order', 'query') as $key) {
			unset($get_params[$key]);
		}

		return count($get_params) > 0;
	}

	public function scheduleQueryLog($query, $category_id, $count = null)
	{
		$query = (string) $query;
		$category_id = (int) $category_id;
		$env = $this->settings->getEnv();

		register_shutdown_function(function () use ($query, $category_id, $count, $env) {
			try {
				$storage = new shopSearchproQueryStorage();
				$storage->save($query, $category_id, $count);
				$env->pushSearchHistory($query);
			} catch (Exception $e) {
			}
		});
	}

	public function getFullPageCacheKey($query, $category_id, shopSearchproEnv $env, array $options = array())
	{
		$ttl = (int) $this->settings->getPageCacheTtl();
		if ($ttl <= 0 || !empty($options['is_empty']) || !empty($options['is_ajax'])) {
			return null;
		}

		if (waRequest::get('page', 1, 'int') > 1) {
			return null;
		}

		$get_params = waRequest::get();
		foreach (array('page', 'sort', 'order', 'query') as $key) {
			unset($get_params[$key]);
		}
		if ($get_params) {
			return null;
		}

		$parts = array(
			$query,
			(int) $category_id,
			$env->getCurrentStorefront(),
			(string) waRequest::get('sort', ''),
			(string) waRequest::get('order', ''),
			(string) ifset($options, 'category_mode', ''),
			(string) ifset($options, 'category_inline_mode_style', ''),
			(string) ifset($options, 'filter_position', ''),
		);

		return md5(implode('|', $parts));
	}
}
