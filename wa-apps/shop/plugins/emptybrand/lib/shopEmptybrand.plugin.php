<?php
class shopEmptybrandPlugin extends shopPlugin
{
    public function backendMenu()
    {
        $this->addJs('js/emptybrand.js');
    }
}
