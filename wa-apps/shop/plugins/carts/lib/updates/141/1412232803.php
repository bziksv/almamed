<?php
/**
 * Should to remove old versions of files.
 * Installer didn't do this.
 */
$dir = realpath(dirname(__FILE__)."/../../actions").'/shopCartsPlugin';
$files = array(
    $dir.'BackendMenu.controller.php',
    $dir.'BackendMessage.controller.php',
    $dir.'BackendReport.action.php',
    $dir.'Settings.action.php',
);
foreach($files as $file) {
    try {
        waFiles::delete($file);
    } catch (Exception $e) {
        // :(
    }
}