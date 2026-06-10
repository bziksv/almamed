<?php

class shopSearchproCategoriesFinder extends shopSearchproEntityFinder implements shopSearchproEntityFinderInterface
{
	protected $model;

	/**
	 * @return shopCategoryModel
	 */
	protected function getModel()
	{
		if(!isset($this->model)) {
			$this->model = new shopCategoryModel();
		}

		return $this->model;
	}

	private function setJoins($query, &$joins)
	{
		if($search_fields = $this->getParams('search_fields')) {
			$fields = ifset($search_fields, 'categories', array());

			if(!empty($fields['seo_plugin']) || !empty($fields['seo_names'])) {
				$joins[] = "LEFT JOIN `shop_seo_category_settings` AS sscs ON sscs.category_id = m.id AND sscs.group_storefront_id = 0 AND sscs.name = 'seo_name'";
			}
		}
	}

	private function setWhereOrder($query, &$where, &$order)
	{
		if($search_fields = $this->getParams('search_fields')) {
			$fields = ifset($search_fields, 'categories', array());

			$wheres = array();
			$orders = array();
			if(!empty($fields['names'])) {
				$sql_where = "m.name LIKE '%{$query}%'";
				$wheres[] = $sql_where;
				$orders[] = "$sql_where DESC";
			}

			if(!empty($fields['descriptions'])) {
				$sql_where = "m.description LIKE '%{$query}%'";
				$wheres[] = $sql_where;
				$orders[] = "$sql_where DESC";
			}

			if(!empty($fields['seo_plugin'])) {
				$wheres[] = "sscs.value LIKE '%{$query}%'";
				$orders[] = "sscs.value ASC";
			}

			if(!empty($wheres)) {
				$where .= implode(' OR ', $wheres);

				if(!empty($orders)) {
					$order = 'ORDER BY ' . implode(', ', $orders) . ' ';
				}
			} else
				$where .= '0';
		} else {
			$where .= '0';
		}
	}

	protected function getDbSelectQuery()
	{
		$model = $this->getModel();

		$sql_fields = 'm.*';
		if($search_fields = $this->getParams('search_fields')) {
			$fields = ifset($search_fields, 'categories', array());

			if(!empty($fields['seo_names'])) {
				$sql_fields .= ', sscs.value AS seo_name';
			}
		}

		$select = "SELECT {$sql_fields} FROM {$model->getTableName()} AS m";

		return $select;
	}

	/**
	 * Поиск по категориям
	 * @param string|array $query
	 * @param int|null $limit
	 * @return array
	 */
	public function findEntities($query, $limit = null)
	{
		$model = $this->getModel();
		$select = $this->getDbSelectQuery();

		$joins = array();
		$this->setJoins($query, $joins);
		$order = '';

		if(is_array($query)) {
			$where = 'WHERE m.id in (' . implode(',', $query) . ')';
			$order = 'ORDER BY m.id in (' . implode(',', $query) . ')';
		} else {
			$query = $model->escape($query, 'like');
			$where = "WHERE (";
			$this->setWhereOrder($query, $where, $order);
			$where .= ")";
		}

		if($search_fields = $this->getParams('search_fields')) {
			$fields = ifset($search_fields, 'categories', array());

			if(!empty($fields['hide_hidden'])) {
				$where .= " AND m.status = 1";
			}
		}

		if($category_id = $this->getParams('search_in_category_id')) {
			$category = $this->getModel()->getById($category_id);

			if($category['type'] == shopCategoryModel::TYPE_STATIC) {
				$tree = $this->getModel()->getTree($category_id);
				$category_ids = array_keys($tree);
				if(empty($category_ids))
					$category_ids[] = $category_id;

				$where .= ' AND m.id IN (' . implode(',', $category_ids) . ')';
			}
		}

		$join = implode(' ', $joins);
		$sql = "$select $join $where $order";

		$categories = $model->query($sql)->fetchAll('id');

		return $categories;
	}
}