<?php

class shopSearchproPluginFrontendCategoriesController extends waController
{
	public function execute()
	{
		$settings = shopSearchproV2Factory::settings();
		if (!$settings->isEnabled() || !$settings->get('category_filter_status')) {
			$this->respond(array('categories' => array()));
			return;
		}

		$depth = (int) $settings->get('category_filter_deep', 1);
		$tree = shopSearchproV2Factory::categoryTreeService()->getNestedTree($depth);

		$this->respond(array(
			'categories' => $this->slimCategories($tree),
		));
	}

	private function slimCategories(array $categories)
	{
		$result = array();
		foreach ($categories as $category) {
			$item = array(
				'id' => (int) $category['id'],
				'name' => (string) $category['name'],
			);
			if (!empty($category['childs'])) {
				$item['childs'] = $this->slimCategories($category['childs']);
			}
			$result[] = $item;
		}
		return $result;
	}

	private function respond(array $payload)
	{
		$this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->getResponse()->addHeader('Cache-Control', 'public, max-age=3600');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	}
}
