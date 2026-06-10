<?php

class shopAjaxActions extends waJsonActions
{

    function managerAction()
    {
        $users = new shopProductmanagerPlugin();
        $id = waRequest::get('manager_id', "int");
        if($id){
            $manager = $users->get_user($id);
            $this->response = $manager;
        }
    }

}