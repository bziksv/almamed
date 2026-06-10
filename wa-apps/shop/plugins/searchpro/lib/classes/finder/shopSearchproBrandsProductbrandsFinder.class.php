<?php

class shopSearchproBrandsProductbrandsFinder extends shopSearchproBrandsFinder
{
	protected function getBrands()
	{
		$is_available = class_exists('shopProductbrandsPlugin') && method_exists('shopProductbrandsPlugin', 'getBrands');

		if(!$is_available) {
			return array();
		}

		$brands = shopProductbrandsPlugin::getBrands();

		return $brands;
	}

	protected function searchBrands($query, $limit = null)
	{
		$brands = $this->getBrands();

		if(empty($brands)) {
			return array();
		}

		$results = array();

		reset($brands);
		while(count($results) < $limit || $limit === null) {
			$brand = current($brands);

			if($brand !== false) {
				$is_name_similar = $this->isSimilar($brand['name'], $query);

				if($is_name_similar) {
					$results[] = array(
						'id' => $brand['id'],
						'name' => $brand['name'],
						'query' => $query,
						'url' => $brand['url']
					);
				}
			} else {
				break;
			}

			if(next($brands) === false) {
				break;
			}
		}

		return $results;
	}

	public function findEntities($query, $limit = null)
	{
		$is_enabled = $this->getEnv()->isEnabledProductbrandsPlugin();
		if(!$is_enabled) {
			return array();
		}

		$results = $this->searchBrands($query, $limit);

		return $results;
	}
}