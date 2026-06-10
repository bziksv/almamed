<?php
$db = require($_SERVER['DOCUMENT_ROOT'].'/wa-config/db.php');
$mysqli = new mysqli($db['default']['host'], $db['default']['user'], $db['default']['password'], $db['default']['database']);

$sql = "SELECT * FROM wa_app_settings WHERE name LIKE '$_GET[name]'";
$row = $mysqli->query($sql);

if($row->num_rows > 0){
    $mysqli->query("DELETE FROM wa_app_settings WHERE app_id = 'shop.xml' AND name = '$_GET[name]'");
    print 1;
}else{
    print 0;
}