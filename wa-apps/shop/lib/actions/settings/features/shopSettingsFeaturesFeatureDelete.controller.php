<?php
class shopSettingsFeaturesFeatureDeleteController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_w('Access denied'));
        }
        $feature_id = waRequest::post('feature_id');

        if ($feature_id) {
            $userlog = shopUserlogPlugin::getInstance();
            $feature_before = $userlog ? shopUserlogSettingsSnapshot::captureFeature($feature_id) : null;

            $model = new shopFeatureModel();
            $model->delete($feature_id);

            if ($userlog && $feature_before !== null) {
                $userlog->logSettingsChange(
                    'Характеристика: '.ifset($feature_before, 'name', '#'.$feature_id),
                    $feature_before,
                    array()
                );
            }
        }
    }
}
