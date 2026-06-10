<?php

abstract class shopSeofilterBackendJsonController extends waJsonController
{
	protected function preExecute()
	{
		$user_rights = new shopSeofilterUserRights();
		if (!$user_rights->hasRights())
		{
			throw new waException('Доступ запрещен', 403);
		}
	}
}