<?php
/**
 * Profile product page: query count + timing.
 * Usage: php .local/profile-product-page.php [product_url_slug]
 */
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/product/' . ($argv[1] ?? 'avtorefkeratometr-bez-stolika-bez-poverki-rmk-200-kitay') . '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';

$query_count = 0;
$query_ms = 0.0;

$prev_handler = set_error_handler(function () {});

require dirname(__DIR__) . '/index.php';
