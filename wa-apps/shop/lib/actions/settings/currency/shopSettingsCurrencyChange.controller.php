<?php

class shopSettingsCurrencyChangeController extends waJsonController
{
    public function execute()
    {
        $userlog = shopUserlogPlugin::getInstance();
        $settings_before = $userlog ? shopUserlogSettingsSnapshot::captureCurrencies() : null;

        $code = waRequest::post('code', '', waRequest::TYPE_STRING_TRIM);

        if (!$code) {
            $this->errors[] = _w("Unknown currency");
            return;
        }

        $currency_model = new shopCurrencyModel();
        if (!$currency_model->setPrimaryCurrency($code, (bool)waRequest::post('convert'))) {
            $this->errors[] = _w("Error when change");
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