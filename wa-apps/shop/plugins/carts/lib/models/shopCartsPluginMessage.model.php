<?php

class shopCartsPluginMessageModel extends waModel {

    protected $table = 'shop_carts_plugin_message';

    public function getMenu()
    {
        return $this->select('id, name, type, delay')->order('delay')->fetchAll();
    }

    public function getByDelay()
    {
        return $this->select('*')->where('status = 1')->order('delay')->fetchAll();
    }
}