<?php

$csm = new shopCartsPluginStorefrontModel();
$clm = new shopCartsPluginLogModel();
$ccm = new shopCartsPluginContactModel();

/*
$days = 30;
$codes = $csm->select('code')->where('edit_datetime < (NOW() - interval '.$days.' day)')->fetchAll(null, true);


if($codes) {
    $count = count($codes);
    $limit = 500;
    $offset = 0;

    while($offset < $count) {
        $_codes = array_slice($codes, $offset, $limit);
        $offset += $limit;

        $clm->deleteByField('code', $_codes);
        $ccm->deleteByField('code', $_codes);
    }
}
$clm->query('DELETE FROM '.$clm->getTableName().' WHERE sent < (NOW() - interval '.$days.' day)');
$csm->query('DELETE FROM '.$csm->getTableName().' WHERE edit_datetime < (NOW() - interval '.$days.' day)');

try {
    $clm->query('OPTIMIZE TABLE '.$clm->getTableName());
    $ccm->query('OPTIMIZE TABLE '.$ccm->getTableName());
    $csm->query('OPTIMIZE TABLE '.$csm->getTableName());
} catch(waDbException $e) {

}
*/

// start для тех, у кого начал отрабатывать update 1515621004.php
try {
    $csm->query('ALTER TABLE `shop_carts_plugin_storefront` DROP `id`');
} catch (Exception $e) {

}
try {
    $csm->query('ALTER TABLE  `shop_carts_plugin_storefront` ADD PRIMARY KEY (  `code` )');
} catch (Exception $e) {

}
// end для тех, у кого начал отрабатывать update 1515621004.php

try {
    $csm->query('SELECT id FROM shop_carts_plugin_storefront WHERE 0');
} catch (Exception $e) {
    $csm->query('ALTER TABLE `shop_carts_plugin_storefront` ADD `id` INT UNSIGNED NULL DEFAULT NULL FIRST;');
}
try {
    $csm->query('SELECT storefront_id FROM shop_carts_plugin_log WHERE 0');
} catch (Exception $e) {
    $csm->query('ALTER TABLE `shop_carts_plugin_log` ADD `storefront_id` INT NULL DEFAULT NULL AFTER `message_id`;');
}


/*
$carts = $csm->query('SELECT `code` FROM shop_carts_plugin_storefront WHERE 1 ORDER BY edit_datetime')->fetchAll();
$i = 0;
foreach ($carts as $cart) {
    $csm->updateByField(array(
        'code' => $cart['code'],
    ), array(
        'id' => ++$i
    ));

    $clm->query('UPDATE shop_carts_plugin_log SET storefront_id = ? WHERE code = ?', $i, $cart['code']);
}*/

try {
    $csm->query('SET @i := 0');
    $csm->query('UPDATE shop_carts_plugin_storefront SET id = (@i := @i+1)');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}

try {
    $clm->query('UPDATE `shop_carts_plugin_log` l
    INNER JOIN shop_carts_plugin_storefront s ON l.code = s.code
    SET l.storefront_id = s.id');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}

try {
    $clm->query('DELETE FROM shop_carts_plugin_log WHERE storefront_id IS NULL');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}




try {
    $csm->query('ALTER TABLE `shop_carts_plugin_storefront` DROP PRIMARY KEY;');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}

try {
    $csm->query('ALTER TABLE `shop_carts_plugin_storefront` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(`id`);');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}
try {
    $csm->query('ALTER TABLE `shop_carts_plugin_storefront` ADD INDEX(`code`);');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}
try {
    $csm->query('ALTER TABLE `shop_carts_plugin_log` ADD INDEX(`storefront_id`);');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}
try {
    $csm->query('ALTER TABLE `shop_carts_plugin_log` DROP `code`;');
} catch (Exception $e) {
    //var_dump($e->getMessage());
}