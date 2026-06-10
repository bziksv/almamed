<?php

class shopSeofilterAppSitemapStructureHandler extends shopSeofilterHookHandler
{
	protected function handle()
	{
		return array(
			'is_shown' => $this->settings->use_sitemap_hook,
		);
	}

	protected function defaultHandleResult()
	{
		return array(
			'is_shown' => false,
		);
	}
}