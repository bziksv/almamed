<?php

class shopProductfeaturesPluginSettingsAction extends waViewAction
{
    public function execute()
    {

        $type_model = new shopTypeModel();
        $types = $type_model->getAll('id');

        $type_features_model = new shopTypeFeaturesModel();
        $rows = $type_features_model->order('sort')->fetchAll();
        foreach ($rows as $row) {
            if (isset($types[$row['type_id']])) {
                $types[$row['type_id']]['features'][] = $row['feature_id'];
            }
        }
        $this->view->assign('types', $types);


        $feature_model = new shopFeatureModel();
        $features = $feature_model->getAll('id');
        $this->view->assign('features', $features);

        $plugin = wa()->getPlugin('productfeatures');
        $this->view->assign('settings', $plugin->getSettings());
    }
}