<?php

/**
 * Enable or disable given discount type when user toggles iButton.
 */
class shopSettingsDiscountsEnableController extends waJsonController
{
    public function execute()
    {
        $type = waRequest::request('type');
        if (!$type) {
            return;
        }
        $userlog = shopUserlogPlugin::getInstance();
        $settings_before = $userlog ? shopUserlogSettingsSnapshot::captureDiscounts() : null;

        $asm = new waAppSettingsModel();
        $asm->set('shop', 'discount_'.$type, waRequest::request('enable') ? 1 : null);

        if ($userlog && $settings_before !== null) {
            $userlog->logSettingsChange(
                'Скидки',
                $settings_before,
                shopUserlogSettingsSnapshot::captureDiscounts()
            );
        }
    }
}

