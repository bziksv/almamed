<?php

class kmapometrWidget extends waWidget
{
    protected $params;

    public function defaultAction()
    {
        $nocache = $this->getRequest()->get('nocache');

        $this->display(array(
            'widget_id' => $this->id,
            'widget_url' => $this->getStaticUrl(),
            'widget_app' => $this->info['app_id'],
            'widget_name' => $this->info['widget'],
            'size' => $this->info['size'],
            'se' => $this->getSettings('se'),
            'city' => $this->getSettings('city'),
            'info' => $this->getInfo()
        ));
    }
}