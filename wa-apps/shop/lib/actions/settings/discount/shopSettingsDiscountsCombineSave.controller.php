<?php

/**
 * Save setting: how to conbine several applicable discounts, max or sum.
 */
class shopSettingsDiscountsCombineSaveController extends waJsonController
{
    public function execute()
    {
        $userlog = shopUserlogPlugin::getInstance();
        $settings_before = $userlog ? shopUserlogSettingsSnapshot::captureDiscounts() : null;

        $asm = new waAppSettingsModel();
        $asm->set('shop', 'discounts_combine', waRequest::request('value') === 'sum' ? 'sum' : 'max');

        if ($userlog && $settings_before !== null) {
            $userlog->logSettingsChange(
                'Скидки',
                $settings_before,
                shopUserlogSettingsSnapshot::captureDiscounts()
            );
        }
    }
}

