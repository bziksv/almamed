<?php

class shopSliderPluginFrontendStatController extends waJsonController
{
    public function execute()
    {
        if (waRequest::method() !== 'post') {
            return;
        }

        $id = waRequest::post('id', 0, 'int');
        $type = waRequest::post('type', '', 'string');

        if (!$id || !in_array($type, array('view', 'click'), true)) {
            $this->errors = 'invalid request';
            return;
        }

        $model = new shopSliderModel();
        $slide = $model->getById($id);
        if (!$slide || !shopSliderPlugin::isSlideVisible($slide)) {
            return;
        }

        $column = $type === 'view' ? 'views_count' : 'clicks_count';
        $model->exec(
            'UPDATE `shop_slider` SET `' . $column . '` = `' . $column . '` + 1 WHERE `id` = ?',
            $id
        );

        $this->response = array('ok' => true);
    }
}
