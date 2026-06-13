<?php

class shopSettingsAffiliateEnableController extends waJsonController
{
    public function execute()
    {
        $userlog = shopUserlogPlugin::getInstance();
        $settings_before = $userlog ? shopUserlogSettingsSnapshot::captureAffiliate() : null;

        $asm = new waAppSettingsModel();
        $asm->set('shop', 'affiliate', waRequest::post('enable') ? '1' : null);

        if ($userlog && $settings_before !== null) {
            $userlog->logSettingsChange(
                'Партнёрская программа',
                $settings_before,
                shopUserlogSettingsSnapshot::captureAffiliate()
            );
        }
    }
}
