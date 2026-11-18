<?php

class shopAbdisplayPluginBackendForceController extends waJsonController
{
    public function execute()
    {
        if (!shopAbdisplayHelper::canForce()) {
            throw new waRightsException('Access denied');
        }
        $key = waRequest::post('key', '', waRequest::TYPE_STRING_TRIM);
        $code = waRequest::post('code', '', waRequest::TYPE_STRING_TRIM);
        $clear = waRequest::post('clear', 0, waRequest::TYPE_INT);

        $experiments = shopAbdisplayHelper::getExperiments();
        if ($key === '' || empty($experiments[$key])) {
            $this->errors = 'Unknown experiment';
            return;
        }
        $abtest_id = (int) $experiments[$key]['abtest_id'];
        if ($clear) {
            shopAbdisplayHelper::clearForceCookie($key);
            $this->response = array('ok' => 1, 'cleared' => 1);
            return;
        }
        $code = strtoupper(preg_replace('~[^A-Za-z0-9]~', '', $code));
        if ($code === '' || $abtest_id <= 0) {
            $this->errors = 'Need code and abtest_id';
            return;
        }
        shopAbdisplayHelper::forceCookie($key, $abtest_id, $code);
        $this->response = array('ok' => 1, 'key' => $key, 'code' => $code);
    }
}
