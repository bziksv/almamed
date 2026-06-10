<?php

class shopCartsPluginContactModel extends waModel {

    protected $table = 'shop_carts_plugin_contact';
    protected $id = 'code';

    public function getContactByCode($code)
    {
        if($contact = $this->getByField('code', $code)) {

            if(!empty($contact['contact_id'])) {
                return new shopCartsPluginCustomer($contact['contact_id']);
            } elseif(!empty($contact['contact'])) {
                $contact = preg_replace('/^O:9:"waContact"/', 'O:23:"shopCartsPluginCustomer"', $contact['contact']);
                $contact = preg_replace('/^O:12:"shopCustomer"/', 'O:23:"shopCartsPluginCustomer"', $contact);
                return @unserialize($contact);
            }
        }
        return false;
    }

    /**
     * @param waContact $contact
     * @param string $code
     */
    public function saveContact($contact, $code)
    {
        $old_contact = $this->getContactByCode($code);
        $contact = shopCartsPluginCustomer::from($contact, $old_contact);
        if(
            $contact->get('name') ||
            $contact->get('email', 'default') ||
            $contact->get('phone', 'default')
        ) {
            if ($contact->getId()) {
                $contact->save();
            } else {
                $this->insert(array(
                    'code' => $code,
                    'contact' => $contact->serialize()
                ), 1);
            }
        }
    }
}