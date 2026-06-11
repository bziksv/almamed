<?php

class shopSearchproPluginFrontendPopularController extends waController
{
	public function execute()
	{
		$settings = shopSearchproV2Factory::settings();
		if (!$settings->isEnabled() || !$settings->get('dropdown_popular_is_visible')) {
			$this->respond(array('popular' => array()));
			return;
		}

		$limit = waRequest::get('limit', 0, 'int');
		if ($limit <= 0) {
			$limit = null;
		}

		$popular = shopSearchproV2Factory::popularService()->getTop($limit);
		$items = array();
		foreach ($popular as $row) {
			$item = array(
				'query' => $row['query'],
				'url' => $row['url'],
				'category_id' => (int) ifset($row, 'category_id', 0),
			);
			if (!empty($row['category_name'])) {
				$item['category_name'] = $row['category_name'];
			}
			$items[] = $item;
		}

		$this->respond(array('popular' => $items));
	}

	private function respond(array $payload)
	{
		$this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->getResponse()->addHeader('Cache-Control', 'public, max-age=300');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	}
}
