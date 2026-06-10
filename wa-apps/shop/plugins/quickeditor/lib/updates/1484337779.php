<?php

/*
 * Metaupdate
 */

$delete_files = array(
    wa()->getAppPath('plugins/quickeditor/templates/actions/quickeditor/QuickeditorPageLink.html', 'shop'),
    wa()->getAppPath('plugins/quickeditor/templates/actions/quickeditor/QuickeditorSettings.html', 'shop'),
);

try {
    foreach ($delete_files as $file) {
        if (file_exists($file)) {
            waFiles::delete($file, true);
        }
    }
} catch (Exception $e) {
}
