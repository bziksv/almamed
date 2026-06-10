<?php

class shopSearchproHistoryFinder extends shopSearchproEntityFinder implements shopSearchproEntityFinderInterface
{
	protected $results_route_url;
	protected $env;

	protected function getEnv()
	{
		if(!isset($this->env)) {
			$this->env = new shopSearchproEnv();
		}

		return $this->env;
	}

	protected function getResultsRouteUrl()
	{
		if(!isset($this->results_route_url)) {
			$this->results_route_url = wa()->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro')) . '/%QUERY%/';
		}

		return $this->results_route_url;
	}

	protected function getResultsUrl($query)
	{
		$encoded_query = shopSearchproUtil::encodeQueryUrl($query);
		$results_route_url = $this->getResultsRouteUrl();
		$url = str_replace('%QUERY%', $encoded_query, $results_route_url);

		return $url;
	}

	protected function searchHistory($query, $limit = null)
	{
		$history = $this->getEnv()->getSearchHistory(true);
		$results = array();

		$i = 0;
		while(count($results) < $limit || $limit === null) {
			if(array_key_exists($i, $history)) {
				$history_query = $history[$i];

				if($this->isSimilar($history_query, $query)) {
					$url = $this->getResultsUrl($history_query);

					$results[] = array(
						'id' => $i,
						'name' => $history_query,
						'query' => $query,
						'url' => $url
					);
				}
			} else {
				break;
			}

			$i++;
		}

		return $results;
	}

	/**
	 * Поиск по истории
	 * @param string $query
	 * @param int|null $limit
	 * @return array
	 */
	public function findEntities($query, $limit = null)
	{
		$results = $this->searchHistory($query, $limit);

		return $results;
	}
}