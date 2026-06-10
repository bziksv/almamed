<?php

class shopSearchproUtil
{
	private $shop_product_model;
	private $shop_category_model;
	private $shop_category_products_model;

	/**
	 * @return shopProductModel
	 */
	private function getShopProductModel()
	{
		if(!isset($this->shop_product_model))
			$this->shop_product_model = new shopProductModel();

		return $this->shop_product_model;
	}

	/**
	 * @return shopCategoryModel
	 */
	private function getShopCategoryModel()
	{
		if(!isset($this->shop_category_model))
			$this->shop_category_model = new shopCategoryModel();

		return $this->shop_category_model;
	}

	/**
	 * @return shopCategoryProductsModel
	 */
	private function getShopCategoryProductsModel()
	{
		if(!isset($this->shop_category_products_model))
			$this->shop_category_products_model = new shopCategoryProductsModel();

		return $this->shop_category_products_model;
	}

	public function getProducts(shopProductsCollection $collection)
	{
		$from_and_where = $collection->getSQL();

		$sql = "SELECT *\n";
		$sql .= $from_and_where;

		$data = $this->getShopProductModel()->query($sql)->fetchAll('id');
		if(!$data) {
			return array();
		}

		return $data;
	}

	public function getProductIds(shopProductsCollection $collection)
	{
		$products = $this->getProducts($collection);

		return array_keys($products);
	}

	public function getCollectionCategories(shopProductsCollection $collection)
	{
		$from_and_where = $collection->getSQL();

		$count_sql = "SELECT p.category_id, COUNT(p.id) AS count ";
		$count_sql .= $from_and_where;
		$count_sql .= "\nGROUP BY p.category_id";

		$alias = $collection->addJoin(array(
			'table' => 'shop_category',
			'on' => 'p.category_id = :table.id',
		));

		$count_alias = $collection->addJoin(array(
			'table' => " ({$count_sql} )",
			'on' => 'p.category_id = :table.category_id',
		));

		$from_and_where = $collection->getSQL();

		$sql = "SELECT {$alias}.*, {$count_alias}.count \n";
		$sql .= $from_and_where;
		$sql .= "\nGROUP BY p.category_id";

		$data = $this->getShopProductModel()->query($sql)->fetchAll('id');
		if(!$data) {
			return array();
		}

		return $data;
	}

	public function getProductsCategories($products, $params = array())
	{
		if($products instanceof shopProductsCollection) {
			$collection = $products;
			$products = $this->getProducts($collection);
		}

		//sort($products);
		$default_params = array(
			'all' => true,
			'workup' => true,
			'ids' => false,
			'initial' => false
		);
		$params = array_merge($default_params, $params);

		if(!empty($products)) {
			$categories = array();

			if($params['all']) {
				$sql = <<<SQL
SELECT c.*
	FROM {$this->getShopCategoryProductsModel()->getTableName()} AS cp
	LEFT JOIN {$this->getShopCategoryModel()->getTableName()} AS c
		ON cp.category_id = c.id
	WHERE
		cp.product_id = ?
		AND c.status = 1
GROUP BY c.id
SQL;
			} else {
				if($params['ids']) {
					$sql = false;
					$product_ids = $products;

					$products = $this->getShopProductModel()->getByField('id', $product_ids, 'id');
				} elseif($params['initial']){
					$sql = false;
				} else {
					$sql = <<<SQL
SELECT c.*
	FROM {$this->getShopProductModel()->getTableName()} AS p
	LEFT JOIN {$this->getShopCategoryModel()->getTableName()} AS c
		ON p.category_id = c.id
	WHERE
		p.id = ?
		AND p.category_id IS NOT NULL
		AND c.status = 1
GROUP BY c.id
SQL;
				}
			}

			foreach($products as $product) {
				$id = $product['id'];

				if($sql) {
					$product_categories = $this->getShopCategoryProductsModel()->query($sql, $id)->fetchAll('id');
				} else {
					if(!$product['category_id']) {
						continue;
					}

					$product_categories = array($product['category_id'] => array());
				}

				if($params['workup']) {
					foreach($product_categories as $id => &$product_category) {
						if(!$id) {
							continue;
						}

						if(isset($product['query'])) {
							$product_category['query'] = $product['query'];
						}

						$product_category['relevancy'] = 1;

						if(array_key_exists($id, $categories)) {
							$product_category['count'] = $categories[$id]['count'] + 1;
						} else {
							$product_category['count'] = 1;
						}
					}
				}

				$categories = self::replace($categories, $product_categories);
			}

			if(!$sql) {
				foreach($categories as $id => &$category) {
					$category_data = $this->getShopCategoryModel()->getById($id);
					$category = array_merge($category_data, $category);
				}
			}

			return $categories;
		}

		return array();
	}

	public function buildQuery($params)
	{
		$subject = http_build_query($params);

		$query = preg_replace_callback('/%5[bdBD](?=[^&]*=)/', array($this, 'buildQueryPregReplaceCallback'), $subject);

		return $query;
	}

	public static function buildQueryPregReplaceCallback($matches)
	{
		return urldecode($matches[0]);
	}

	public static function replaceRecursive(array $base = array(), array $replacements = array())
	{
		if(!function_exists('array_replace_recursive')) {
			function array_replace_recursive(array $base = array(), array $replacements = array()) {
				foreach(array_slice(func_get_args(), 1) as $replacements) {
					$bref_stack = array(&$base);
					$head_stack = array($replacements);

					do {
						end($bref_stack);

						$bref = &$bref_stack[key($bref_stack)];
						$head = array_pop($head_stack);

						unset($bref_stack[key($bref_stack)]);

						foreach(array_keys($head) as $key) {
							if(isset($key, $bref) &&
								isset($bref[$key]) && is_array($bref[$key]) &&
								isset($head[$key]) && is_array($head[$key])
							) {
								$bref_stack[] = &$bref[$key];
								$head_stack[] = $head[$key];
							} else {
								$bref[$key] = $head[$key];
							}
						}
					} while(count($head_stack));
				}

				return $base;
			}
		}

		return array_replace_recursive($base, $replacements);
	}

	public static function replace(array $base = array(), array $replacements = array())
	{
		if(!function_exists('array_replace')) {
			function array_replace(array &$base, array &$replacements = array()) {
				$args = func_get_args();
				$count = func_num_args();

				for($i = 0; $i < $count; ++$i) {
					if(is_array($args[$i])) {
						foreach($args[$i] as $key => $val) {
							$base[$key] = $val;
						}
					}
				}

				return $base;
			}
		}

		return array_replace($base, $replacements);
	}

	public static function encodeQueryUrl($query)
	{
		$query = str_replace('/', '%SLASH%', $query);
		$query = urlencode($query);

		return $query;
	}
}
