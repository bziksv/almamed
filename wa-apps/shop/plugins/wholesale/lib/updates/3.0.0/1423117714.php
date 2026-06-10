<?php

$domains_settings = array();
if (method_exists('shopWholesale', 'saveDomainsSettings')) {
    shopWholesale::saveDomainsSettings($domains_settings);
}

