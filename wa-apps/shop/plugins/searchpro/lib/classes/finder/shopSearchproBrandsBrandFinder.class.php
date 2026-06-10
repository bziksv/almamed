<?php

class shopSearchproBrandsBrandFinder extends shopSearchproBrandsFinder
{
	protected $model;
	protected $page_model;

	/**
	 * @return shopBrandBrandModel
	 */
	protected function getModel()
	{
		if(!isset($this->model)) {
			$this->model = new shopBrandBrandModel();
		}

		return $this->model;
	}

	/**
	 * @return shopBrandBrandPageModel
	 */
	protected function getPageModel()
	{
		if(!isset($this->page_model)) {
			$this->page_model = new shopBrandBrandPageModel();
		}

		return $this->page_model;
	}

	private function setWhereSelect($query, &$where, &$select)
	{
		if($search_fields = $this->getParams('search_fields')) {
			$fields = ifset($search_fields, 'brands', array());

			$wheres = array();
			$wheres[] = "m.name LIKE '%{$query}%'";

			if(!empty($wheres))
				$where .= implode(' OR ', $wheres);
			else
				$where .= '0';
		} else {
			$where .= '0';
		}
	}

	protected function fillBrandUrls(&$brands)
	{
		$route_params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'brandPage',
			'brand' => '%URL%',
		);

		$url = wa()->getRouting()->getUrl('shop', $route_params, true);

		foreach($brands as &$brand) {
			$brand['url'] = str_replace('%URL%', $brand['brand_url'], $url);
		}
	}

	public function findEntities($query, $limit = null)
	{
		$is_enabled = $this->getEnv()->isEnabledBrandPlugin();
		if(!$is_enabled) {
			return array();
		}

		$model = $this->getModel();

		$query = $model->escape($query, 'like');

		$select = $this->getDbSelectQuery();
		$where = "WHERE (";
		$this->setWhereSelect($query, $where, $select);
		$where .= ")";

		$sql = "$select $where";
		if($limit !== null) {
			$limit = $model->escape($limit, 'int');
			$sql .= " LIMIT $limit";
		}

		$brands = $model->query($sql)->fetchAll('id');

		$this->fillBrandUrls($brands);

		return $brands;
	}
}