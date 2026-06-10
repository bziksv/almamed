<?php

$path = wa()->getDataPath('data/easyinvoicephys/', true, 'shop', false);
try {
    waFiles::delete($path);
} catch (Exception $ex) {
    waLog::log($ex->getMessage());
}
