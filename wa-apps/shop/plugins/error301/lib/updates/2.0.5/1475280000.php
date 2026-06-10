<?php
$model = new waModel();
$model->query("DELETE FROM shop_error301 WHERE `type` = 'p' AND `parent` IS NULL;");