<?php

class shopSeofilterBackendMenuHandler extends shopSeofilterHookHandler
{
	protected function handle()
	{
		return array(
			'core_li' => '<li class="no-tab shop-seofilter__li"><a href="?plugin=seofilter">SEO-фильтр</a></li>',
		);
	}

	protected function beforeHandle()
	{
		$user_rights = new shopSeofilterUserRights();

		return $user_rights->hasRights();
	}

	protected function defaultHandleResult()
	{
		return array();
	}
}