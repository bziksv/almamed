<?php

class shopSettingsNotificationsDeleteController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        $userlog = shopUserlogPlugin::getInstance();
        $settings_before = $userlog ? shopUserlogSettingsSnapshot::captureNotification($id) : null;

        $model = new shopNotificationModel();
        if (!$model->delete($id)) {
            $this->errors = 'Error, try again';
            return;
        }

        if ($userlog && $settings_before !== null) {
            $userlog->logSettingsChange(
                'Уведомления',
                $settings_before,
                array()
            );
        }
    }
}
