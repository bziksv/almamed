<?php

$model = new waModel();
try {
    $model->query('SELECT image FROM blog_post WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE blog_post  ADD image VARCHAR(255) NOT NULL');
}