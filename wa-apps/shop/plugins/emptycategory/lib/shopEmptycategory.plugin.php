<?php
class shopEmptycategoryPlugin extends shopPlugin
{
    public function backendMenu()
    {
        $this->addJs('js/emptycategory.js');
    }
}
