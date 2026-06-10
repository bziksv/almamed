<?php
$path = wa()->getConfig()->getAppConfig('shop');
$path = $path->getPluginPath('easyinvoicephys');

waFiles::delete($path."/templates/Settings.html", true);