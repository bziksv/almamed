<?php

class shopCartsPluginLogModel extends waModel {

    protected $table = 'shop_carts_plugin_log';

    public function isSent($message_id, $storefront_id)
    {
        try {
            return (bool) $this->getByField(array(
                'message_id' => $message_id,
                'storefront_id' => $storefront_id,
                'status' => 1,
            ));
        } catch (Exception $e) {
            return false;
        }
    }

    public function getSentById($storefront_id)
    {
        $mm = new shopCartsPluginMessageModel();

        $sql = 'SELECT l.*, m.name message_name FROM '.$this->getTableName().' l '.
            'LEFT JOIN '.$mm->getTableName().' m ON m.id = l.message_id '.
            'WHERE storefront_id = ? ORDER BY l.sent DESC';

        return $this->query($sql, $storefront_id)->fetchAll();
    }

}