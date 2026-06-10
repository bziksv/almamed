<?php
/**
 * Сохранение размеров эскизов изображений определённого типа для категорий
*/
class shopWmimageincatPluginSaveimagesizeController extends waJsonController
{
    function execute()
    {
        $type = waRequest::post('type', waRequest::TYPE_STRING);
        $size = waRequest::post('str', waRequest::TYPE_STRING);
  
        $app_settings = new waAppSettingsModel();
        $app_settings->set(wa()->getApp('shop').'.wmimageincat', $type, $size);
  
        $data = explode('X', $size);
        $data['width'] = array_shift($data);
        $data['height'] = array_shift($data);

        $this->response['size'] = $data;  
    }
}