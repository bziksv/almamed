<?php

$model = new waModel();

$model->query('update `shop_seo_settings` 
set `group_id`=\'category_pagination\', `name`=REPLACE(`name`, \'pagination_\', \'\')
where `group_id`=\'category\' and `name` LIKE \'pagination_%\'');
$model->query('update `shop_seo_template` 
set `group_id`=\'category_pagination\', `name`=REPLACE(`name`, \'pagination_\', \'\')
where `group_id`=\'category\' and `name` LIKE \'pagination_%\'');

$model->query('update `shop_seo_settings` 
set `group_id`=\'product_page\', `name`=REPLACE(`name`, \'page_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'page_%\'');
$model->query('update `shop_seo_template` 
set `group_id`=\'product_page\', `name`=REPLACE(`name`, \'page_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'page_%\'');

$model->query('update `shop_seo_settings` 
set `group_id`=\'product_review\', `name`=REPLACE(`name`, \'review_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'review_%\'');
$model->query('update `shop_seo_template` 
set `group_id`=\'product_review\', `name`=REPLACE(`name`, \'review_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'review_%\'');

$model->query('update `shop_seo_settings` 
set `group_id`=\'brand_category\', `name`=REPLACE(`name`, \'category_\', \'\')
where `group_id`=\'brand\' and `name` LIKE \'category_%\'');
$model->query('update `shop_seo_template` 
set `group_id`=\'brand_category\', `name`=REPLACE(`name`, \'category_\', \'\')
where `group_id`=\'brand\' and `name` LIKE \'category_%\'');

$model->query('update `shop_seo_settings_category` 
set `group_id`=\'subcategory_pagination\', `name`=REPLACE(`name`, \'pagination_\', \'\')
where `group_id`=\'subcategory\' and `name` LIKE \'pagination_%\'');
$model->query('update `shop_seo_template_category` 
set `group_id`=\'subcategory_pagination\', `name`=REPLACE(`name`, \'pagination_\', \'\')
where `group_id`=\'subcategory\' and `name` LIKE \'pagination_%\'');

$model->query('update `shop_seo_settings_category` 
set `group_id`=\'product_page\', `name`=REPLACE(`name`, \'page_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'page_%\'');
$model->query('update `shop_seo_template_category` 
set `group_id`=\'product_page\', `name`=REPLACE(`name`, \'page_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'page_%\'');

$model->query('update `shop_seo_settings_category` 
set `group_id`=\'product_review\', `name`=REPLACE(`name`, \'review_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'review_%\'');
$model->query('update `shop_seo_template_category` 
set `group_id`=\'product_review\', `name`=REPLACE(`name`, \'review_\', \'\')
where `group_id`=\'product\' and `name` LIKE \'review_%\'');