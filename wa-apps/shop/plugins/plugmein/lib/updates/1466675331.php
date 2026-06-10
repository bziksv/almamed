<?php

$path = wa()->getConfig()->getAppConfig('shop');
$path = $path->getPluginPath('plugmein');

waFiles::delete($path."/lib/actions/backend/shopPlugmeinPluginBackendRun.action.php", true);
