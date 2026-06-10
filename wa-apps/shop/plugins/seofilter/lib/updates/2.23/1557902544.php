<?php

$model = new waModel();

$query = $model->query("
SELECT storefront
FROM shop_seofilter_productfilters_settings
WHERE `name` = 'link_type' AND `value` = '1'
");

foreach ($query as $row)
{
	$storefront_param = array(
		'storefront' => $row['storefront']
	);

	$model->exec("
INSERT
INTO shop_seofilter_productfilters_settings
	(storefront, `name`, `value`)
VALUES
	(:storefront, 'link_type', '2'),
	(:storefront, 'custom_link_text', 'другие товары')
ON DUPLICATE KEY UPDATE
	`value` = VALUES(`value`)
", $storefront_param);
}
