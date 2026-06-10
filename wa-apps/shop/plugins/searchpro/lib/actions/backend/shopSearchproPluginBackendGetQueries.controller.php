<?php

class shopSearchproPluginBackendGetQueriesController extends waJsonController
{
	public function execute()
	{
		$type = waRequest::get('type', 'all', waRequest::TYPE_STRING_TRIM);
		$page = waRequest::get('page', 0, waRequest::TYPE_INT);
		$sort = waRequest::get('sort', 'last_datetime', waRequest::TYPE_STRING_TRIM);
		$order = waRequest::get('order', 'desc', waRequest::TYPE_STRING_TRIM);

		$query_storage = new shopSearchproQueryStorage();
		$queries_per_page = shopSearchproQueryStorage::BACKEND_QUERIES_PER_PAGE;

		$offset = ($page - 1) * $queries_per_page;
		$queries = $query_storage->getQueries($offset, $queries_per_page, $sort, $order, $type);

		$this->response = $queries;
	}
}
