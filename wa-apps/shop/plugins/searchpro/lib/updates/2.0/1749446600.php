<?php

waAppConfig::clearAutoloadCache('shop');

$cache = new waVarExportCache('app_settings/shop.searchpro', 86400, 'webasyst');
$cache->delete();
