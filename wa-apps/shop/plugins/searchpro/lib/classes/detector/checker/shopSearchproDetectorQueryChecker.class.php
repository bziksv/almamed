<?php

class shopSearchproDetectorQueryChecker extends shopSearchproDetectorChecker
{
	public function getAction()
	{
		$rule = $this->getRule();
		$value = $rule->getValue();

		$method = "getAction_{$value}";

		if(method_exists($this, $method)) {
			return $this->$method();
		}

		return null;
	}

	/**
	 * @return string
	 */
	protected function getQuery()
	{
		return $this->data;
	}

	/**
	 * @return shopProductModel
	 */
	protected function getProductModel()
	{
		return $this->getEnv()->getProductModel();
	}

	/**
	 * @return shopProductSkusModel
	 */
	protected function getSkusModel()
	{
		return $this->getEnv()->getSkusModel();
	}

	/**
	 * @return shopCategoryModel
	 */
	protected function getCategoryModel()
	{
		return $this->getEnv()->getCategoryModel();
	}

	/**
	 * @param mixed $data
	 * @param string $type
	 * @return null|shopSearchproDetectorAction
	 * @throws waException
	 */
	private function gotoAction($data, $type)
	{
		if(!$data) {
			return null;
		}

		return $this->createAction('goto', array(
			$type => $data
		));
	}

	/**
	 * @param mixed $product
	 * @return null|shopSearchproDetectorAction
	 * @throws waException
	 */
	private function gotoProductAction($product)
	{
		return $this->gotoAction($product, 'product');
	}

	/**
	 * @param mixed $category
	 * @return null|shopSearchproDetectorAction
	 * @throws waException
	 */
	private function gotoCategoryAction($category)
	{
		return $this->gotoAction($category, 'category');
	}

	private function gotoBrandBrandAction($brand)
	{
		return $this->gotoAction($brand, 'brand_brand');
	}

	private function gotoBrandproductbrandsAction($brand)
	{
		return $this->gotoAction($brand, 'brand_productbrands');
	}

	private function getAction_product_id()
	{
		$query = (int) $this->getQuery();

		if(!$query) {
			return null;
		}

		$product = $this->getProductModel()->select('id, url')->where('id = ?', $query)->fetchAssoc();

		return $this->gotoProductAction($product);
	}

	private function getAction_product_name()
	{
		$query = mb_strtolower($this->getQuery());

		$product = $this->getProductModel()->select('id, url')->where('LOWER(name) = ?', $query)->fetchAssoc();

		return $this->gotoProductAction($product);
	}

	private function getAction_sku_name()
	{
		$query = mb_strtolower($this->getQuery());

		$product_model = $this->getProductModel();
		$skus_model = $this->getSkusModel();

		$sql = <<<SQL
SELECT p.id, p.url
	FROM {$skus_model->getTableName()} AS s
	JOIN {$product_model->getTableName()} AS p
		ON p.id = s.product_id
	WHERE LOWER(s.sku) = ?
SQL;

		$product = $skus_model->query($sql, $query)->fetchAssoc();

		return $this->gotoProductAction($product);
	}

	private function getAction_category_name()
	{
		$query = mb_strtolower($this->getQuery());

		$category = $this->getCategoryModel()->select('id, full_url, url')->where('LOWER(name) = ?', $query)->fetchAssoc();

		return $this->gotoCategoryAction($category);
	}

	private function getAction_category_seoname()
	{
		if(!$this->getEnv()->isEnabledSeoPlugin()) {
			return null;
		}

		$query = mb_strtolower($this->getQuery());

		$category_model = $this->getCategoryModel();

		$sql = <<<SQL
SELECT c.id, c.full_url, c.url
	FROM {$category_model->getTableName()} AS c
	JOIN `shop_seo_category_settings` AS sc
		ON sc.category_id = c.id
		AND sc.group_storefront_id = 0
		AND sc.name = 'seo_name'
		AND LOWER(sc.value) = ?
SQL;

		$category = $category_model->query($sql, $query)->fetchAssoc();

		return $this->gotoCategoryAction($category);
	}

	private function getAction_brand_name()
	{
		$env = shopSearchproPlugin::getEnv();

		$query = mb_strtolower($this->getQuery());
		if ($env->isEnabledBrandPlugin())
		{
			$shop_brand_settings_storage = new shopBrandSettingsStorage();
			$shop_brand_settings_storage->getSettings()->is_enabled;
			$brand_model = new shopBrandBrandModel();

			$brand = $brand_model->getByField(array(
				'name' => $query,
				'is_shown' => '1',
			));

			return $this->gotoBrandBrandAction($brand);
		}
		elseif ($env->isEnabledProductbrandsPlugin())
		{
			$productbrands_model = new shopProductbrandsModel();
			$brand = $productbrands_model->getByField(array(
				'name' => $query,
				'hidden' => 0,
			));

			return $this->gotoBrandproductbrandsAction($brand);
		}
		else
		{
			return null;
		}
	}
}