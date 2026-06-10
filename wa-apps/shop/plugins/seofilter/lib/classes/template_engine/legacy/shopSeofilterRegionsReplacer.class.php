<?php

class shopSeofilterRegionsReplacer implements shopSeofilterIReplacer
{
	public function fetch($template)
	{
		$regions_plugin_enabled = wa('shop')->getConfig()->getPluginInfo('regions') !== array();
		$helper_exists = class_exists('shopRegionsViewHelper') && method_exists('shopRegionsViewHelper', 'parseTemplate');

		if ($regions_plugin_enabled && $helper_exists)
		{
			try
			{
				return shopRegionsViewHelper::parseTemplate($template);
			}
			catch (Exception $e)
			{}
		}

		return $template;
	}

	public function toSmarty($template)
	{
		return preg_replace_callback('/\{region_[A-z0-9\_\-]*?\}/',
			array('shopSeofilterSmartyEscaper', 'escape'), $template);
	}
}