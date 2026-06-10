<?php

$path = wa()->getDataPath('brands', true, 'shop');
waFiles::write($path.'/thumb.php', '<?php
$file = realpath(dirname(__FILE__)."/../../../../")."/wa-apps/shop/plugins/productbrands/lib/thumb.php";

if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
');

waFiles::write($path.'/.htaccess', '
<ifModule mod_rewrite.c>
RewriteEngine On

    RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)$ thumb.php [L,QSA]
</ifModule>
');