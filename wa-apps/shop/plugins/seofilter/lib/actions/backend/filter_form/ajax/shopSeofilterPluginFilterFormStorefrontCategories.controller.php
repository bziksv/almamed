<?php

class shopSeofilterPluginFilterFormStorefrontCategoriesController extends shopSeofilterBackendFilterFormJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');
		$state = json_decode($state_json, true);

		$storefront = $state['storefront'];

		if (!strlen($storefront))
		{
			$this->formError('storefront name is empty');

			return;
		}

		$this->response = array(
			'storefront' => $storefront,
			'category_ids' => $this->getStorefrontsCategories($storefront),
		);
	}

	private function getStorefrontsCategories($storefront)
	{
		if (!strlen($storefront))
		{
			return null;
		}

		$model = new waModel();
		$sql = '
SELECT c.id category_id
FROM shop_category c
LEFT JOIN shop_category_routes cr ON cr.category_id = c.id
WHERE cr.category_id IS NULL OR cr.route = :storefront
';

		$rows = $model->query($sql, array('storefront' => $storefront));

		$category_ids = array();
		foreach ($rows as $row)
		{
			$category_ids[] = $row['category_id'];
		}
		return $category_ids;
	}
}