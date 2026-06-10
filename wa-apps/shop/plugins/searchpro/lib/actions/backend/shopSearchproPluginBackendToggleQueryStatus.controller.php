<?php

class shopSearchproPluginBackendToggleQueryStatusController extends waController
{
	public function execute()
	{
		$id = waRequest::post('id', 0, 'int');
		$status = waRequest::post('status') ? '1' : '0';

		$query_model = new shopSearchproQueryModel();
		$query_model->updateById($id, array(
			'status' => $status
		));
	}
}