<?php


class shopSeoWaEnv implements shopSeoEnv
{
	public function isSupportOg()
	{
		wa('shop');
		
		return class_exists('shopCategoryOgModel')
			&& class_exists('shopProductOgModel');
	}
	
	public function isEnabledProductbrands()
	{
		return wa('shop')->getConfig()->getPluginInfo('productbrands') !== array();
	}
	
	public function isEnabledRegions()
	{
		return wa('shop')->getConfig()->getPluginInfo('regions') !== array();
	}
	
	public function isEnabledMyland()
	{
		$info = wa()->getAppInfo('mylang');
		
		if ($info === null)
		{
			return false;
		}
		
		wa('mylang');
		
		return class_exists('mylangHelper') && method_exists('mylangHelper', 'checkSite') && class_exists('mylangViewHelper') && method_exists('mylangViewHelper', 'categories') && method_exists('mylangViewHelper', 'products')
			? mylangHelper::checkSite()
			: false;
	}
}