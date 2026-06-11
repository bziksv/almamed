<?php

class shopSearchproV2Factory
{
	public static function settings()
	{
		return shopSearchproV2Settings::create();
	}

	public static function searchService($context = 'dropdown')
	{
		return new shopSearchproV2SearchService(self::settings(), $context);
	}

	public static function popularService()
	{
		return new shopSearchproV2PopularService(self::settings());
	}

	public static function categoryTreeService()
	{
		return new shopSearchproV2CategoryTreeService(self::settings());
	}

	public static function pageService()
	{
		return new shopSearchproV2PageService(self::settings());
	}
}
