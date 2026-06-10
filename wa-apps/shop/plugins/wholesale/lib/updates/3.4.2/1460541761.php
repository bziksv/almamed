<?php

try {
    $files = array(
        'plugins/wholesale/lib/actions/shopWholesalePluginFrontendWholesale.controller.php',
    );

    foreach ($files as $file) {
        waFiles::delete(wa()->getAppPath($file, 'shop'), true);
    }
} catch (Exception $e) {
    
}