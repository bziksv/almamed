<?php


class shopCartsPluginCustomerSaveController extends waJsonController
{

    public function preExecute()
    {
        $u = $this->getUser();

        if (!($u->isAdmin('shop') || $u->getRights('shop', 'carts_plugin.contacts_report'))) {
            throw new waRightsException(_w("Access denied"));
        }
    }

    public function execute()
    {
        $code = waRequest::post('code', '', waRequest::TYPE_STRING_TRIM);
        $model = new shopCartsPluginContactModel();

        if($customer = $model->getContactByCode($code)) {
            $customer->save();
            $data = array(
                'contact_id' => $customer->getId()
            );
            $storefront_model = new shopCartsPluginStorefrontModel();
            $storefront_model->updateByField('code', $code, $data); // тут обновляем по коду ВСЕ корзины
            $this->response = $data;
            $data['contact'] = null;
            $model->updateById($code, $data);

        } else {
            $this->setError(_wp('An error occurred! This contact cannot be saved.'));
        }
    }
}