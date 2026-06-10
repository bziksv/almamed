<?php

class shopBreadcrumbsOldStorefrontTemplateHelper
{
	public function getStorefrontTemplates($storefront)
	{
		$templates = array();

		$old_css = wa('shop')->getDataPath('plugins/breadcrumbs/', true, 'shop') . md5($storefront) . '/breadcrumbs.css';
		$old_template = wa('shop')->getDataPath('plugins/breadcrumbs/', false, 'shop') . md5($storefront) . '/Breadcrumbs.html';

		if (file_exists($old_css))
		{
			$content = trim(file_get_contents($old_css));

			if (strlen($content))
			{
				$templates['css'] = $content;
			}
		}

		if (file_exists($old_template))
		{
			$content = trim(file_get_contents($old_template));

			if (strlen($content))
			{
				$templates['template'] = $content;
			}
		}

		return $templates;
	}
}