<?php

class shopSearchproPopularFinder extends shopSearchproEntityFinder implements shopSearchproEntityFinderInterface
{
	protected $results_route_url;
	protected $model;

	/**
	 * @return shopSearchproQueryModel
	 */
	protected function getModel()
	{
		if(!isset($this->model)) {
			$this->model = new shopSearchproQueryModel();
		}

		return $this->model;
	}

	protected function getResultsRouteUrl()
	{
		if(!isset($this->results_route_url)) {
			$this->results_route_url = wa()->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro'));
		}

		return $this->results_route_url;
	}

	protected function getResultsUrl($query, $category_id)
	{
		$encoded_query = shopSearchproUtil::encodeQueryUrl($query);
		$results_route_url = $this->getResultsRouteUrl();

		$url = $results_route_url;
		if($category_id) {
			$url .= "/{$category_id}";
		}
		$url .= "/{$encoded_query}/";

		return $url;
	}

	protected function searchPopular($query, $limit = null)
	{
		$model = $this->getModel();

		$query = $model->escape($query, 'like');

		$select = "SELECT p.*, c.name AS category_name FROM {$model->getTableName()} AS p LEFT JOIN `shop_category` AS c ON c.id = p.category_id";
		$where = "WHERE p.status = '1' AND p.query LIKE '%{$query}%'";

		$sql = "$select $where ORDER BY p.frequency DESC";
		if($limit !== null) {
			$limit = $model->escape($limit, 'int');
			$sql .= " LIMIT $limit";
		}

		$popular = $model->query($sql)->fetchAll('id');

		$this->workupPopular($popular, $query);

		return $popular;
	}

	protected function workupPopular(&$popular, $query)
	{
		foreach($popular as &$entity) {
			$url = $this->getResultsUrl($entity['query'], $entity['category_id']);

			$entity['name'] = $entity['query'];
			$entity['url'] = $url;
			$entity['query'] = $query;
		}
	}

	/**
	 * Поиск по популярным запросам
	 * @param string $query
	 * @param int|null $limit
	 * @return array
	 */
	public function findEntities($query, $limit = null)
	{
		$results = $this->searchPopular($query, $limit);

		return $results;
	}
}