<?php

// todo снести этот костыль когда webasyst допилят логику работы с shop_product_skus.available
class shopSeofilterProductsCollectionFixed extends shopSeofilterProductsCollection
{
	/**
	 * @param array $data
	 *
	 * fix косяка движка с отображением товаров с недоступными для заказа артикулами (всего одно отличие от оригинальной функции)
	 * сделано на случай, если плагин выйдет раньше обновления для shop-script
	 */
	public function filters($data)
	{
		$data = shopSeofilterFilterFeatureValuesHelper::normalizeParams($data);

		$product_model = $this->getModel('product');
		if (!method_exists($product_model, 'existsSelectableProducts'))
		{
			parent::filters($data);
		}

		if ($this->filtered) {
			return;
		}

		$config = wa('shop')->getConfig();
		/**
		 * @var shopConfig $config
		 */

		if (!empty($data['in_stock_only'])) {
			$this->where[] = '(p.count > 0 OR p.count IS NULL)';
		} elseif (!empty($data['out_of_stock_only'])) {
			$this->where[] = 'p.count <= 0';
		}

		$price_filter = array();

		if (isset($data['price_min']) && $data['price_min'] !== '') {
			$this->where[] = 'p.max_price >= '.$this->toFloat(shop_currency($data['price_min'], true, $config->getCurrency(true), false));
			$price_filter['price_min'] = ' >= '.$this->toFloat(shop_currency($data['price_min'], true, $config->getCurrency(true), false));
		}
		if (isset($data['price_max']) && $data['price_max'] !== '') {
			$this->where[] = 'p.min_price <= '.$this->toFloat(shop_currency($data['price_max'], true, $config->getCurrency(true), false));
			$price_filter['price_max'] = ' <='.$this->toFloat(shop_currency($data['price_max'], true, $config->getCurrency(true), false));
		}
		unset(
			$data['in_stock_only'],
			$data['out_of_stock_only'],
			$data['price_min'],
			$data['price_max']
		);

		$feature_model = new shopFeatureModel();
		$features = shopSeofilterFilterFeatureValuesHelper::getFeatures('code', array_keys($data), 'code');

		if ($features && $product_model->existsSelectableProducts()) {
			// fix
			//$skus_alias = $this->addJoin('shop_product_skus', ':table.product_id = p.id', ':table.available = 1');
			$skus_alias = $this->addJoin('shop_product_skus', ':table.product_id = p.id');
			if (waRequest::param('drop_out_of_stock') == 2) {
				$this->addWhere('(' . $skus_alias . '.count IS NULL OR ' . $skus_alias . '.count > 0)');
			}
		}
		$alias_index = 1;
		foreach ($data as $feature_code => $values) {
			if (!is_array($values)) {
				if ($values === '') {
					continue;
				}
				$values = array($values);
			}
			if (isset($features[$feature_code])) {
				if (isset($values['min']) || isset($values['max']) || isset($values['unit'])) {
					if (ifset($values['min'], '') === '' && ifset($values['max'], '') === '') {
						continue;
					} else {
						$unit = ifset($values['unit']);
						$min = $max = null;
						if (isset($values['min']) && $values['min'] !== '') {
							$min = $values['min'];
							if ($unit) {
								$min = shopDimension::getInstance()->convert($min, $features[$feature_code]['type'], null, $unit);
							}
						}
						if (isset($values['max']) && $values['max'] !== '') {
							$max = $values['max'];
							if ($unit) {
								$max = shopDimension::getInstance()->convert($max, $features[$feature_code]['type'], null, $unit);
							}
						}
						$fm = $feature_model->getValuesModel($features[$feature_code]['type']);
						$values = $fm->getValueIdsByRange($features[$feature_code]['id'], $min, $max);
					}
				} else {
					foreach ($values as & $v) {
						$v = (int)$v;
					}
				}
				if ($values) {
					if (wa('shop')->getConfig()->getOption('filters_features') == 'exists') {
						$t = 'tpf'.($alias_index++);
						$this->where[] = 'EXISTS (
                        SELECT 1 FROM shop_product_features '.$t.' WHERE
                            p.id = '.$t.'.product_id AND '.$t.'.feature_id = '.(int)$features[$feature_code]['id'].' AND
                            '.$t.'.feature_value_id IN ('.implode(',', $values).')'.
							(!empty($skus_alias) ? ' AND ('.$t.'.sku_id IS NULL OR '.$t.'.sku_id = '.$skus_alias.'.id)' : '').'
                        )';
					} else {
						$on = 'p.id = :table.product_id AND :table.feature_id = '.(int)$features[$feature_code]['id'];
						$where = ':table.feature_value_id IN ('.implode(',', $values).')';
						if (!empty($skus_alias)) {
							$where .= ' AND (:table.sku_id IS NULL OR :table.sku_id = '.$skus_alias.'.id)';
						}

						$this->addJoin('shop_product_features', $on, $where);
					}
					$this->group_by = 'p.id';
					if (!empty($skus_alias) && !empty($price_filter)) {
						// #53.4890
						foreach ($price_filter as $price_filter_item) {
							$this->addWhere('('.$skus_alias.'.price '.$price_filter_item.')');
						}
					}
				} else {
					$this->where[] = '0';
				}
			}
		}
		$this->filtered = true;
	}
}