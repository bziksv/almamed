<?php

class shopSearchproPluginFrontendSuggestController extends waController
{
	public function execute()
	{
		$settings = shopSearchproV2Factory::settings();
		if (!$settings->isEnabled()) {
			return;
		}

		$query = waRequest::get('q', '');
		$category_id = waRequest::get('category_id', 0, 'int');

		$normalizer = new shopSearchproV2QueryNormalizer();
		$query_normalized = $normalizer->normalize($query);
		$present_cache = new shopSearchproV2ResultCache($settings->getDropdownCacheTtl());
		$cached_presented = $present_cache->get('dropdown_presented', $query_normalized, $category_id);

		$this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->getResponse()->addHeader('Cache-Control', 'private, max-age=120');

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
}
