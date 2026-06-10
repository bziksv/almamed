<?php
/**
 * Формирование ссылки для предпросмотра изображения определённого типа для категории
*/
class shopWmimageincatPluginPreviewimageController extends waJsonController
{
    /**
     * Преобразование строки вида "width X height" в массив данных
     *
     * @param string $data - "width X height"
     * @return array - ('width' => int, 'height' => int);
    */
    protected function convert_to_array($data)
    {
        if (is_array($data)) {
            return $data;
        } else {
            $data = explode('X', $data);
            $data['width'] = array_shift($data);
            $data['height'] = array_shift($data);
            return $data;
        }
    }
  
  
    function execute()
    {
        $id = waRequest::post('id', waRequest::TYPE_INT);
        $ext = waRequest::post('ext', waRequest::TYPE_STRING);
        $type = waRequest::post('type', waRequest::TYPE_STRING);
        $cat_id = waRequest::post('cat_id', waRequest::TYPE_INT);
 
  
        $path = wa()->getDataUrl("wmimageincatPlugin/categories/{$cat_id}/", true, 'shop');
        $path = $path.$type.'_'.$id.'.'.$ext;
        $this->response['path'] = $path;
    }
}