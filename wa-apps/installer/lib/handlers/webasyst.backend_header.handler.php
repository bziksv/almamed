<?php

/**
 * Hearing
 */
class installerWebasystBackend_headerHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $header_top = $this->getAnnouncements();
        return array('header_top' => $header_top);
    }

    protected function getAnnouncements()
    {
        // Almamed: store/installer banners (PHP version, license warnings, ads) — permanently off
        return '';
    }
}
