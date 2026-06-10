<?php


class shopCartsPluginCustomer extends shopCustomer {

    /**
     * @param waContact $contact
     * @param waContact|null $old_contact
     * @return shopCartsPluginCustomer
     */
    public static function from($contact, $old_contact = null)
    {
        $new_contact = new self();

        if($old_contact) {
            if($old_contact->getId()) {
                $new_contact = $old_contact;
            } else {
                foreach ($old_contact as $field => $value) {
                    $new_contact->$field = $value;
                }
                $new_contact->data = $old_contact->load();
            }
            if(is_array($contact->data)) {
                foreach($contact->data as $key => $value) {
                    if($key == 'id') continue;
                    if($value = self::prepareValue($value)) {
                        $new_contact->set($key, $value);
                    }
                }
            }
        } else {
            $new_contact->data = $contact->data;
        }

        return $new_contact;
    }

    public function serialize()
    {
        $str = @serialize($this);
        return $str;
    }

    public function hasOrders()
    {
        if($this->getId()) {
            $m = new shopOrderModel();

            $count = $m->select("COUNT(contact_id)")
                ->where('contact_id=?', $this->getId())
                ->fetchField();

            return $count > 0;
        } else {
            return false;
        }
    }


    /**
     * @param $value
     * @return array|string
     */
    public static function prepareValue($value)
    {
        if(is_array($value)) {
            $result = array();
            foreach ($value as $key => $v) {
                if($v = self::prepareValue($v)) {
                    $result[$key] = $v;
                }
            }
            return $result;
        } else {
            return trim($value);
        }
    }


}