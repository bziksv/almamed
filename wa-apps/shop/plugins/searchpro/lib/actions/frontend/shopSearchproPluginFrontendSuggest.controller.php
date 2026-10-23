<?php

class shopSearchproPluginFrontendSuggestController extends waController
{
	/** Soft per-IP cap when nginx limit_req is missing (requests / 60s). */
	const RATE_LIMIT_PER_MINUTE = 60;

	public function execute()
	{
		$settings = shopSearchproV2Factory::settings();
		if (!$settings->isEnabled()) {
			return;
		}

		$this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->getResponse()->addHeader('Cache-Control', 'private, max-age=120');

		if ($this->isRateLimited()) {
			$this->getResponse()->setStatus(429);
			echo json_encode(array('error' => 'rate_limited', 'products' => array(), 'categories' => array()), JSON_UNESCAPED_UNICODE);
			return;
		}

		$query = waRequest::get('q', '');
		$category_id = waRequest::get('category_id', 0, 'int');

		$normalizer = new shopSearchproV2QueryNormalizer();
		$query_normalized = $normalizer->normalize($query);
		$present_cache = new shopSearchproV2ResultCache($settings->getDropdownCacheTtl());
		$cached_presented = $present_cache->get('dropdown_presented', $query_normalized, $category_id);

		if ($cached_presented !== null) {
			echo json_encode($cached_presented, JSON_UNESCAPED_UNICODE);
			return;
		}

		$service = shopSearchproV2Factory::searchService('dropdown');
		$result = $service->suggest($query, $category_id);
		$presenter = new shopSearchproV2SuggestPresenter($settings);
		$presented = $presenter->present($result);
		$present_cache->set('dropdown_presented', $query_normalized, $category_id, $presented);
		echo json_encode($presented, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * File-cache counter per IP (best-effort; nginx limit_req is the real gate).
	 */
	private function isRateLimited()
	{
		$ip = waRequest::getIp();
		if (!$ip) {
			return false;
		}
		$key = 'suggest_rl_' . md5($ip);
		$cache = new waSerializeCache($key, 60, 'shop/searchpro/ratelimit');
		$count = 0;
		if ($cache->isCached()) {
			$cached = $cache->get();
			$count = is_array($cached) ? (int) ifset($cached, 'n', 0) : (int) $cached;
		}
		$count++;
		$cache->set(array('n' => $count));
		return $count > self::RATE_LIMIT_PER_MINUTE;
	}
}
