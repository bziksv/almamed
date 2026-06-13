<?php

class shopSettingsFeaturesTypeSaveController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        $model = new shopTypeModel();

        $userlog = shopUserlogPlugin::getInstance();
        $type_id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $type_before = $userlog && $type_id ? shopUserlogSettingsSnapshot::captureProductType($type_id) : null;

        $data = array();
        $data['id'] = $type_id;
        switch (waRequest::post('source', 'custom')) {
            case 'custom':
                $data['name'] = waRequest::post('name');
                $data['icon'] = waRequest::post('icon_url', false, waRequest::TYPE_STRING_TRIM);
                if (empty($data['icon'])) {
                    $data['icon'] = waRequest::post('icon', 'icon.box', waRequest::TYPE_STRING_TRIM);
                }

                if (!empty($data['id'])) {
                    $model->updateById($data['id'], $data);
                } else {
                    $data['sort'] = $model->select('MAX(sort)+1 as max_sort')->fetchField('max_sort');
                    $data['id'] = $model->insert($data);
                }
                break;
            case 'template':
                $data = $model->insertTemplate(waRequest::post('template'), true);
                break;
        }

        if ($data) {
            $data['icon_html'] = shopHelper::getIcon($data['icon'], 'icon.box');
            $data['name_html'] = '<span class="js-type-icon">'.$data['icon_html'].'</span>
                    <span class="js-type-name">'.htmlspecialchars($data['name'], ENT_QUOTES, 'utf-8').'</span>';
        }
        $this->response = $data;

        if ($userlog && !empty($data['id'])) {
            $after = shopUserlogSettingsSnapshot::captureProductType($data['id']);
            $userlog->logSettingsChange(
                'Тип товара: '.ifset($after, 'name', ifset($data, 'name', '')),
                $type_before !== null ? $type_before : array(),
                $after
            );
        }
    }
}
