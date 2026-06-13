<?php

class shopSettingsCurrencyAddController extends waJsonController
{
    public function execute()
    {
        $userlog = shopUserlogPlugin::getInstance();
        $settings_before = $userlog ? shopUserlogSettingsSnapshot::captureCurrencies() : null;

        $code = waRequest::post('code', '', waRequest::TYPE_STRING_TRIM);

        if (!$code) {
            $this->errors[] = _w("Unknown code");
            return;
        }
        $currency_model = new shopCurrencyModel();
        $this->response = $currency_model->add($code);
        if (!$this->response) {
            $this->errors[] = _w("Unknown code");
            return;
        }

        if ($userlog && $settings_before !== null) {
            $userlog->logSettingsChange(
                'Валюты',
                $settings_before,
                shopUserlogSettingsSnapshot::captureCurrencies()
            );
        }
    }
}
