<?php

abstract class shopSeofilterLongActionController extends waLongActionController
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