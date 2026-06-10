<?php

$whatsnew = dirname(__FILE__).'/whatsnew.php';
if(file_exists($whatsnew)) {
    $whatsnew = include $whatsnew;
    end($whatsnew);
    $key = key($whatsnew);

    $model = new waAppSettingsModel();
    $model->set(array('shop', 'carts'), 'whatsnew', $key);
}