<?php


class shopSeofilterProductsCollection extends shopProductsCollection
{
	public function getPriceRange()
	{
		$skus_table_alias = null;

		foreach ($this->joins as $join)
		{
			if ($join['table'] == 'shop_product_skus')
			{
				$skus_table_alias = $join['alias'];

				break;
			}
		}

		if ($skus_table_alias === null)
		{
			$skus_table_alias = $this->addJoin('shop_product_skus', ':table.product_id = p.id', ':table.price <> 0');
		}
		else
		{
			$this->addWhere("`{$skus_table_alias}`.price <> 0");
		}

		$alias_currency = $this->addJoin('shop_currency', ':table.code = p.currency');
		$prev_group_by = $this->group_by;
		$this->groupBy("p.id");
		$sql = $this->getSQL();
		$sql = "SELECT MIN(`{$skus_table_alias}`.price * `{$alias_currency}`.rate) min, MAX(`{$skus_table_alias}`.price * `{$alias_currency}`.rate) max " . $sql;
		$data = $this->getModel()->query($sql)->fetch();
		$this->group_by = $prev_group_by;
		array_pop($this->joins);

		return array(
			'min' => (double)(isset($data['min']) ? $data['min'] : 0),
			'max' => (double)(isset($data['max']) ? $data['max'] : 0),
		);
	}

	/**
	 * @param string|array $table Table name to be used in JOIN clause.
	 *     Alternatively an associative array may be specified containing values for all method parameters.
	 *     In this case $on and $where parameters are ignored.
	 * @param string $on ON condition FOR JOIN, must not include 'ON' keyword
	 * @param string $where WHERE condition for SELECT, must not include 'WHERE' keyword
	 * @return string Specified table's alias to be used in SQL query
	 */
	public function addLeftJoin($table, $on = null, $where = null)
	{
		$alias = $this->addJoin($table, $on, $where);

		$last_join = array_pop($this->joins);
		$last_join['type'] = 'LEFT';
		$this->joins[] = $last_join;

		return $alias;
	}

	public function filterStorefront($storefront)
	{
		if ($this->hash[0] != 'category')
		{
			return;
		}

		$this->prepare();

		if ($this->info['type'] == shopCategoryModel::TYPE_DYNAMIC)
		{
			return;
		}

		$category_products_alias = 'cp';

		if (!isset($this->join_index[$category_products_alias]))
		{
			$alias = $this->addJoin('shop_category_products');
		}
		else
		{
			$alias = 'cp1';
		}

		$storefront_escaped = $this->getModel()->escape($storefront);

		$this->addLeftJoin(
			'shop_category_routes',
			"{$alias}.category_id = :table.category_id",
			"(:table.route IS NULL OR :table.route = '{$storefront_escaped}')"
		);
	}
}