<?php

try {
    $files = array(
        'plugins/wholesale/templates/BackendProductSkuSettings.html',
        'plugins/wholesale/templates/CategoryField.html',
        'plugins/wholesale/templates/FrontendCart.html',
        'plugins/wholesale/templates/FrontendProduct.html',
        'plugins/wholesale/templates/Shipping.html',
        'plugins/wholesale/lib/classes/shopWholesaleHelper.class.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginBackendSaveCategory.controller.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginFrontendCart.controller.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginFrontendProduct.controller.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginFrontendShipping.controller.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginSettings.action.php',
        'plugins/wholesale/lib/actions/shopWholesalePluginSettingsRoute.action.php',
    );

    foreach ($files as $file) {
        waFiles::delete(wa()->getAppPath($file, 'shop'), true);
    }
} catch (Exception $e) {
    
}