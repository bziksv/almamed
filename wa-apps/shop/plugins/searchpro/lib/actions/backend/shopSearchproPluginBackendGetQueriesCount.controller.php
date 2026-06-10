<?php

class shopSearchproPluginBackendGetQueriesCountController extends waJsonController
{
	public function execute()
	{
		$type = waRequest::get('type', 'all', waRequest::TYPE_STRING_TRIM);

		$query_storage = new shopSearchproQueryStorage();
		$queries_count = $query_storage->getQueriesCount($type);

		$this->response = $queries_count;
	}
}
