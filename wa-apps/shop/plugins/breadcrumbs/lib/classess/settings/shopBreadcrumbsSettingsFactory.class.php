<?php

class shopBreadcrumbsSettingsFactory
{
	public function getAppSettings($app)
	{
		if ($app == 'shop')
		{
			return new shopBreadcrumbsSettings();
		}
		elseif ($app == 'blog')
		{
			return new shopBreadcrumbsBlogSettings();
		}
		else
		{
			return null;
		}
	}
}