<?php
class shopWmimageincatPluginSettingsAction extends waViewAction
{
     /**
      *Преобразование строки вида "width X height" в массив данных
      *
      *@param string - "width X height"
      *@return array - ('width' => int, 'height' => int);
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
        $plugin = wa()->getPlugin('wmimageincat');
        $this->view->assign('banner', $this->convert_to_array($plugin->getSettings('banner')));
        $this->view->assign('image', $this->convert_to_array($plugin->getSettings('image')));
        $this->view->assign('icon', $this->convert_to_array($plugin->getSettings('icon')));
    }
 
}