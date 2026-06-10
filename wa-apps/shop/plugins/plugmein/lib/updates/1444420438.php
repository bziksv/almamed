<?php

$path = wa()->getConfig()->getAppConfig('shop');
$path = $path->getPluginPath('plugmein');

waFiles::delete($path."/css/plugmein.css", true);
