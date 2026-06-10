<?php
class shopSeofieldPlugin extends shopPlugin
{
    public function backendMenu()
    {
        $this->addJs('js/seofield.js');
    }
}
