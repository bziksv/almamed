<?php

class shopWaitPluginFrontendWaitclosetopController extends waJsonController
{

    /*
     * delete cookie
     */
    public function execute()
    {
        $ajax = waRequest::isXMLHttpRequest();

        if ($ajax) {
		
			wa()->getResponse()->setCookie('wait_code', '', time() - 3600, '/');
			
		}
    }

}