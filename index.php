<?php
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

$local_dev_hosts = array('localhost:8080', 'almamed.su:8080', '127.0.0.1:8080');
if (!in_array($_SERVER['HTTP_HOST'] ?? '', $local_dev_hosts, true)) {
    register_shutdown_function(function () {
        require_once __DIR__ . '/clickfrogru_udp_tcp.php';
        ClickfrogUDPSender::sendto();
    });
}

if(preg_match('/\/komplektatsiya\//',$_SERVER['REQUEST_URI'],$preg)){
    header( 'Location: https://'.$_SERVER['HTTP_HOST'].str_replace('komplektatsiya/','',$_SERVER['REQUEST_URI']), true, 301 );
}

$arUrl = parse_url($_SERVER['REQUEST_URI']);
$prefix_delite = substr($arUrl['path'], 0, -3);
$prefix = substr($arUrl['path'], -3);
if($prefix == '-r/'){
    $query = ($arUrl['query']) ? '?' . $arUrl['query'] : "";
	header( 'Location: https://' . $_SERVER['HTTP_HOST'] . $prefix_delite . '/' . $query, true, 301 );
}else {
	$path = dirname(__FILE__) . '/wa-config/SystemConfig.class.php';
	if (file_exists($path)) {
		require_once($path);
		waSystem::getInstance(null, new SystemConfig())->dispatch();
	} else {
		$path = dirname(__FILE__) . '/wa-installer/install.php';
		if (file_exists($path)) {
			require_once($path);
		} else {
			//404
		}
	}
}
