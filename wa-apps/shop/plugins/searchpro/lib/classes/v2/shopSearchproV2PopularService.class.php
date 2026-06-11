<?php

class shopSearchproV2PopularService
{
	private $settings;
	private $query_model;

	public function __construct(shopSearchproV2Settings $settings)
	{
		$this->settings = $settings;
	}

	public function getTop($limit = null)
	{
		if ($limit === null) {
			$limit = (int) $this->settings->get('dropdown_popular_max_count', 5);
		}

		$model = $this->getQueryModel();
		$rows = $model->getVisible($limit);

		$results_route_url = $this->settings->getEnv()->getRouteUrl(
			'shop/frontend/page/',
			array('plugin' => 'searchpro')
		);

		foreach ($rows as &$entity) {
			$url = $results_route_url;
			$encoded_query = shopSearchproUtil::encodeQueryUrl($entity['query']);
			if (!empty($entity['category_id'])) {
				$url .= '/' . $entity['category_id'];
			}
			$url .= '/' . $encoded_query . '/';
			$entity['name'] = $entity['query'];
			$entity['url'] = $url;
		}
		unset($entity);

		return $rows;
	}

	/**
	 * Фильтрация top-N popular из кэша getVisible() вместо SQL LIKE по всей таблице.
	 */
	public function matchQuery($query, $limit = null)
	{
		$query = (string) $query;
		if ($query === '') {
			return array();
		}

		if (!$this->settings->get('dropdown_popular_status') || !$this->settings->get('dropdown_popular_is_visible')) {
			return array();
		}

		if ($limit === null) {
			$limit = (int) $this->settings->get('dropdown_popular_max_count', 5);
		}
		$limit = max(1, (int) $limit);

		$rows = $this->getQueryModel()->getVisible(max($limit * 10, 50));
		$results_route_url = $this->settings->getEnv()->getRouteUrl(
			'shop/frontend/page/',
			array('plugin' => 'searchpro')
		);

		$results = array();
		foreach ($rows as $entity) {
			if (mb_stripos($entity['query'], $query) === false) {
				continue;
			}

			$url = $results_route_url;
			$encoded_query = shopSearchproUtil::encodeQueryUrl($entity['query']);
			if (!empty($entity['category_id'])) {
				$url .= '/' . $entity['category_id'];
			}
			$url .= '/' . $encoded_query . '/';

			$id = (int) $entity['id'];
			$results[$id] = array(
				'id' => $id,
				'name' => $entity['query'],
				'query' => $query,
				'url' => $url,
				'category_id' => (int) ifset($entity, 'category_id', 0),
				'category_name' => ifset($entity, 'category_name', ''),
			);

			if (count($results) >= $limit) {
				break;
			}
		}

		return $results;
	}

	private function getQueryModel()
	{
		if ($this->query_model === null) {
			$this->query_model = new shopSearchproQueryModel();
		}
		return $this->query_model;
	}
}
