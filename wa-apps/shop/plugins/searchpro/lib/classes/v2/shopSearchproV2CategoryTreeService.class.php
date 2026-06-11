<?php

class shopSearchproV2CategoryTreeService
{
	private $settings;
	private $env;

	public function __construct(shopSearchproV2Settings $settings)
	{
		$this->settings = $settings;
		$this->env = $settings->getEnv();
	}

	public function getNestedTree($depth = null)
	{
		if ($depth === null) {
			$depth = (int) $this->settings->get('category_filter_deep', 1);
		}

		$source = $this->getFlatTree($depth);
		return $this->buildNested($source);
	}

	public function getFlatTree($depth)
	{
		$route = $this->env->getCurrentStorefront();
		$cache_key = 'tree_' . md5($route . '|' . (int) $depth);
		$cache = new waSerializeCache($cache_key, 3600, 'shop/searchpro/categories');

		if ($cache->isCached()) {
			$cached = $cache->get();
			if (is_array($cached)) {
				return $cached;
			}
		}

		$data = $this->env->getCategoryModel()->getTree(0, $depth, true, $route);
		$cache->set($data);

		return $data;
	}

	private function buildNested(array $source_categories)
	{
		$stack = array();
		$categories = array();

		foreach ($source_categories as $category) {
			$is_hidden_parent = $category['parent_id'] && $category['id']
				&& !isset($source_categories[$category['parent_id']]);

			if ($category['status'] === '0' || $is_hidden_parent) {
				continue;
			}

			$category['childs'] = array();
			$l = count($stack);

			while ($l > 0 && $stack[$l - 1]['depth'] >= $category['depth']) {
				array_pop($stack);
				$l--;
			}

			if ($l == 0) {
				$i = count($categories);
				$categories[$i] = $category;
				$stack[] = &$categories[$i];
			} else {
				$i = count($stack[$l - 1]['childs']);
				$stack[$l - 1]['childs'][$i] = $category;
				$stack[] = &$stack[$l - 1]['childs'][$i];
			}
		}

		return $categories;
	}
}
