<?php

abstract class shopSeofilterBackendFiltersListJsonController extends shopSeofilterBackendJsonController
{
	protected function formError($message)
	{
		$this->errors[] = $message;
	}

	protected function formSuccess($data = array(), $message = '')
	{
		$response = $data;

		$backend_filters_list_page = new shopSeofilterBackendFiltersListPage();
		$rules_list = $backend_filters_list_page->getFiltersList();

		if (!$rules_list || $rules_list['has_errors'])
		{
			$err_log_timestamp = time();
			$this->formError("Can not fetch rules from database (search info in seofilter_errors.log by timestamp [{$err_log_timestamp}])");
		}

		$response['filters'] = $rules_list['filters'];
		$response['pagination'] = $rules_list['pagination'];
		$response['total_count'] = $rules_list['total_count'];
		$response['per_page'] = $backend_filters_list_page->getPerPage();

		if ($message)
		{
			$response['message'] = $message;
		}

		$this->response = $response;
	}
}