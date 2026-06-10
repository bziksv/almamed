<?php
class shopProducthiddenPlugin extends shopPlugin
{
    public function backendMenu()
    {
        waSystem::getInstance()->getResponse()->addCss('wa-content/js/jquery-plugins/jquery-tagsinput/jquery.tagsinput.css');
        waSystem::getInstance()->getResponse()->addJs('wa-content/js/jquery-plugins/jquery-tagsinput/jquery.tagsinput.min.js');
        $this->addJs('js/producthidden.js');
    }
}
