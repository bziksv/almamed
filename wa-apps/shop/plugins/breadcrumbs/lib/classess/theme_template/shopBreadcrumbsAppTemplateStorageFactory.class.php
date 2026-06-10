<?php

class shopBreadcrumbsAppTemplateStorageFactory
{
	public function getAppThemeTemplateStorage($app, $theme = null)
	{
		if ($app == 'shop')
		{
			return new shopBreadcrumbsShopTemplateStorage($theme);
		}
		elseif ($app == 'blog')
		{
			return new shopBreadcrumbsBlogTemplateStorage($theme);
		}
		else
		{
			return null;
		}
	}
}