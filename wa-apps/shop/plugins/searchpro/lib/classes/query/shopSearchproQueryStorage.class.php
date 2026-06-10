<?php

class shopSearchproQueryStorage
{
	const QUERY_HISTORY_KEY = 'shop_searchpro_query';
	const BACKEND_QUERIES_PER_PAGE = 30;

	protected $query_model;
	protected $user_storage_instance;
	protected $user_storage;
	protected $results_route_url;
	protected $category_results_route_url;

	/**
	 * @return shopSearchproQueryModel
	 */
	protected function getQueryModel()
	{
		if(!isset($this->query_model)) {
			$this->query_model = new shopSearchproQueryModel();
		}

		return $this->query_model;
	}

	/**
	 * @return waSessionStorage
	 */
	protected function getUserStorageInstance()
	{
		if(!isset($this->user_storage_instance)) {
			$this->user_storage_instance = wa()->getStorage();
		}

		return $this->user_storage_instance;
	}

	protected function getData($query, $category_id)
	{
		return array($query, $category_id);
	}

	/**
	 * @return array
	 */
	protected function readUserStorage()
	{
		if(!isset($this->user_storage)) {
			$this->user_storage = $this->getUserStorageInstance()->read(self::QUERY_HISTORY_KEY);

			if($this->user_storage === null || !is_array($this->user_storage)) {
				$this->user_storage = array();
			}
		}

		return $this->user_storage;
	}

	/**
	 * @param string $query
	 * @return bool
	 */
	protected function isInUserStorage($query, $category_id)
	{
		$user_storage = $this->readUserStorage();
		$data = $this->getData($query, $category_id);

		return in_array($data, $user_storage);
	}

	/**
	 * @param string $query
	 */
	protected function writeUserStorage($query, $category_id)
	{
		$user_storage = $this->readUserStorage();

		$data = $this->getData($query, $category_id);
		$user_storage[] = $data;

		$this->getUserStorageInstance()->write(self::QUERY_HISTORY_KEY, $user_storage);
	}

	public function save($query, $category_id, $count = null)
	{
		$is_in_user_storage = $this->isInUserStorage($query, $category_id);

		if(!$is_in_user_storage) {
			$this->writeUserStorage($query, $category_id);

			return $this->getQueryModel()->save($query, $category_id, $count);
		}

		return false;
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

	protected function workupQuery(&$query)
	{
		$query['url'] = $this->getResultsUrl($query['query'], $query['category_id']);
	}

	public function getQueries($offset = null, $limit = null, $sort = null, $order = null, $type = 'all', $is_workup = true)
	{
		$queries = $this->getQueryModel()->getQueries($offset, $limit, $sort, $order, $type);

		foreach($queries as &$query) {
			$query['first_datetime_human'] = wa_date('humandatetime', $query['first_datetime']);
			$query['last_datetime_human'] = wa_date('humandatetime', $query['last_datetime']);

			if($is_workup) {
				$this->workupQuery($query);
			}
		}

		return $queries;
	}

	public function getQueriesCount($type = 'all')
	{
		return $this->getQueryModel()->getCount($type);
	}
}
