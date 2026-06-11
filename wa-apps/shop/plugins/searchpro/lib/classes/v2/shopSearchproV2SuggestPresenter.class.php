<?php

class shopSearchproV2SuggestPresenter
{
	private $settings;
	private $env;
	private $currency;
	private $category_names = array();

	public function __construct(shopSearchproV2Settings $settings = null)
	{
		$this->settings = $settings !== null ? $settings : shopSearchproV2Settings::create();
		$this->env = $this->settings->getEnv();
	}

	public function present(shopSearchproV2SuggestResult $result)
	{
		$query = $result->query;
		$category_id = (int) $result->category_id;
		$encoded_query = shopSearchproUtil::encodeQueryUrl($query);

		$results_url = $this->env->getRouteUrl('shop/frontend/page/', array('plugin' => 'searchpro'));
		if ($category_id) {
			$results_url .= '/' . $category_id;
		}
		$results_url .= '/' . $encoded_query . '/';

		$this->category_names = $this->loadCategoryNames($result->results);

		$groups = array();
		foreach ($this->settings->getEntitiesSort() as $group_id) {
			$entities = ifset($result->results, $group_id, array());
			if (!$entities) {
				continue;
			}

			$presented = array();
			foreach ($entities as $entity) {
				$item = $this->presentEntity($group_id, $entity, $query, $category_id);
				if ($item) {
					$presented[] = $item;
				}
			}

			if ($presented) {
				$groups[] = array(
					'id' => $group_id,
					'title' => shopSearchproPluginViewHelper::getGroupTitle($group_id),
					'entities' => $presented,
				);
			}
		}

		return array(
			'query' => $query,
			'category_id' => $category_id,
			'count' => $result->count,
			'results_url' => $results_url,
			'groups' => $groups,
		);
	}

	private function loadCategoryNames(array $results)
	{
		$ids = array();

		foreach ($results as $group_id => $entities) {
			if ($group_id === 'products') {
				foreach ($entities as $entity) {
					if (!empty($entity['category_url']) || !empty($entity['category_full_url'])) {
						continue;
					}
					if (!empty($entity['category_id'])) {
						$ids[(int) $entity['category_id']] = true;
					}
				}
			} elseif ($group_id === 'categories') {
				foreach ($entities as $entity) {
					if (!empty($entity['parent_name'])) {
						continue;
					}
					if (!empty($entity['existing_name']) && !empty($entity['parent_id'])) {
						$ids[(int) $entity['parent_id']] = true;
					}
				}
			} elseif (in_array($group_id, array('history', 'popular'), true)) {
				foreach ($entities as $entity) {
					if (!empty($entity['category_name'])) {
						continue;
					}
					if (!empty($entity['category_id'])) {
						$ids[(int) $entity['category_id']] = true;
					}
				}
			}
		}

		if (!$ids) {
			return array();
		}

		return (new shopCategoryModel())->getById(array_keys($ids));
	}

	private function getCategoryName($category_id, array $entity = array())
	{
		if (!empty($entity['parent_name'])) {
			return $entity['parent_name'];
		}
		if (!empty($entity['category_name'])) {
			return $entity['category_name'];
		}

		$category_id = (int) $category_id;
		if (!$category_id) {
			return null;
		}

		if (isset($this->category_names[$category_id])) {
			return ifset($this->category_names[$category_id], 'name', null);
		}

		return null;
	}

	private function presentEntity($group_id, $entity, $query, $category_id)
	{
		switch ($group_id) {
			case 'products':
				return $this->presentProduct($entity);
			case 'categories':
				return $this->presentCategory($entity, $query, $category_id);
			case 'brands':
				return $this->presentBrand($entity);
			case 'history':
			case 'popular':
				return $this->presentQueryEntity($entity);
			default:
				return null;
		}
	}

	private function presentProduct(array $entity)
	{
		$name = ifset($entity, 'name', '');
		if ($name === '') {
			return null;
		}

		$show_image = (bool) $this->settings->get('dropdown_products_image_status');
		$show_price = (bool) $this->settings->get('dropdown_products_price_status');

		$item = array(
			'type' => 'products',
			'name' => $name,
			'action' => 'goto:href',
			'href' => $this->getProductHref($entity),
		);

		if ($show_image && !empty($entity['image_id'])) {
			$item['image'] = shopImage::getUrl(array(
				'product_id' => (int) ifset($entity, 'id', 0),
				'id' => (int) $entity['image_id'],
				'filename' => ifset($entity, 'image_filename', ''),
				'ext' => ifset($entity, 'ext', 'jpg'),
			), '48x48');
		}

		if ($show_price) {
			$price = (float) ifset($entity, 'price', 0);
			if ($price <= 0) {
				$item['price_on_request'] = true;
			} else {
				$item['price_on_request'] = false;
				$item['price_html'] = shop_currency_html($price, $this->getCurrencyCode());
				$compare = (float) ifset($entity, 'compare_price', 0);
				if ($compare > 0 && abs($compare - $price) > 0) {
					$item['compare_price_html'] = shop_currency_html($compare, $this->getCurrencyCode());
				}
			}
		}

		return $item;
	}

	private function presentCategory(array $entity, $query, $category_id)
	{
		$name = ifset($entity, 'name', '');
		if ($name === '') {
			return null;
		}

		$category_url = ifset($entity, 'category_results_url', '');
		if ($category_url === '') {
			$category_url = $this->env->getRouteUrl('shop/frontend/category', array(
				'category_url' => ifset($entity, 'url', ifset($entity, 'full_url', '')),
			));
		}

		$item = array(
			'type' => 'categories',
			'name' => $name,
			'action' => 'goto:data-link',
			'href' => $category_url,
			'data_link' => $category_url,
		);

		if (!empty($entity['existing_name']) && !empty($entity['parent_id'])) {
			$subname = $this->getCategoryName($entity['parent_id'], $entity);
			if ($subname !== null) {
				$item['subname'] = $subname;
			}
		}

		return $item;
	}

	private function presentBrand(array $entity)
	{
		$name = ifset($entity, 'name', '');
		$url = ifset($entity, 'url', '');
		if ($name === '' || $url === '') {
			return null;
		}

		return array(
			'type' => 'brands',
			'name' => $name,
			'action' => 'goto:data-link',
			'href' => $url,
			'data_link' => $url,
		);
	}

	private function presentQueryEntity(array $entity)
	{
		$name = ifset($entity, 'name', ifset($entity, 'query', ''));
		$url = ifset($entity, 'url', '');
		if ($name === '') {
			return null;
		}

		$item = array(
			'type' => 'query',
			'name' => $name,
			'action' => $url ? 'goto:data-link' : 'value:data-value',
			'data_value' => $name,
		);

		if ($url) {
			$item['href'] = $url;
			$item['data_link'] = $url;
		}

		if (!empty($entity['category_id'])) {
			$subname = $this->getCategoryName($entity['category_id'], $entity);
			if ($subname !== null) {
				$item['subname'] = $subname;
			}
		}

		return $item;
	}

	private function getCurrencyCode()
	{
		if ($this->currency === null) {
			$this->currency = $this->env->getConfig()->getCurrency(false);
		}
		return $this->currency;
	}

	private function getProductHref(array $entity)
	{
		if (!empty($entity['href'])) {
			return $entity['href'];
		}

		if (!empty($entity['category_full_url']) || !empty($entity['category_url'])) {
			return shopSearchproPluginViewHelper::getProductUrl($entity);
		}

		$category_id = (int) ifset($entity, 'category_id', 0);
		if ($category_id && isset($this->category_names[$category_id])) {
			$category = $this->category_names[$category_id];
			$entity['category_url'] = ifset($category, 'url', '');
			$entity['category_full_url'] = ifset($category, 'full_url', ifset($category, 'url', ''));
			return shopSearchproPluginViewHelper::getProductUrl($entity);
		}

		return shopSearchproPluginViewHelper::getProductUrl($entity);
	}
}
