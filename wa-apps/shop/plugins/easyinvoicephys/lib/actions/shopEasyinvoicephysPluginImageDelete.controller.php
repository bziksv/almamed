<?php

class shopEasyinvoicephysPluginImageDeleteController extends waJsonController
{
    public function execute()
    {
		$plugin_id = 'easyinvoicephys';
        $plugin = wa()->getPlugin($plugin_id);
		$path = wa()->getDataPath($plugin_id, true, 'shop');		
		
		foreach (array('faximile_image', 'stamp_image') as $img) {
			if ( waRequest::get($img) ) {
			$settings = array(
				$file_name = $img.'.png',
				'delete_'.$img => 1
				);					
			}
		}		
		waFiles::delete($path.'/'.$file_name, true);			
        $this->response = $plugin->saveSettings($settings);
    }
}