<?php

$plugin_dir = wa('shop')->getAppPath('plugins/carts/');
waFiles::delete($plugin_dir.'/lib/actions/report/shopCartsPluginReportOld.action.php');
waFiles::delete($plugin_dir.'/templates/actions/report/ReportOld.html');