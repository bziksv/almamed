<?php

class shopCartsPluginStorefrontModel extends waModel {

    protected $table = 'shop_carts_plugin_storefront';
    protected $id = 'id';

    public function getLastByCode($code)
    {
        return $this->select('*')
            ->where('code = ?', $code)
            ->order('id DESC')
            ->fetchAssoc();
    }

    public function getStorefrontByCode($code)
    {
        if($data = $this->getLastByCode($code)) {
            return $data['storefront'];
        }
        return null;
    }


    public function deleteLastByCode($code)
    {
        if($data = $this->getLastByCode($code)) {
            $this->deleteById($data['id']);
        }
    }

    public function getContactIdsByRange($from, $to)
    {
        return $this->select('id, contact_id')
            ->where('cancel = 0 AND order_id IS NULL AND edit_datetime BETWEEN ? AND ?', $from, $to)
            ->fetchAll('id', true);
    }

    public function getContactByCode($code)
    {
        $contact_id = $this->select('contact_id')->where('code=?',$code)->fetchField();

        if($contact_id) {
            return new shopCartsPluginCustomer($contact_id);
        } else {
            $ccm = new shopCartsPluginContactModel();
            return $ccm->getContactByCode($code);
        }
    }


    public function getReportData($params, $offset, $on_page)
    {
        $sql = $this->getReportSql($params);

        $data = $this->query('SELECT s.*'.$sql.'ORDER BY s.edit_datetime DESC LIMIT '.$offset.','.$on_page)->fetchAll('id');

        if($data) {
            $ccm = new shopCartsPluginContactModel();
            $ccp = new shopCartsPluginCartProducts();
            $srm = new shopCartsPluginStorefrontRefererModel();

            $sources = $srm->select('*')->where('storefront_id IN (?)', array(array_keys($data)))
                ->order('create_datetime ASC') // хитрый способ выбрать только последние записи
                ->fetchAll('storefront_id', true);

            foreach($data as &$d) {
                if($d['contact_id']) {
                    try {
                        $d['contact'] = new shopCartsPluginCustomer($d['contact_id']);
                        $d['contact']->load();
                    } catch (Exception $e) {
                        // dummy contact if not exists
                        $d['contact'] = new shopCartsPluginCustomer();
                        $d['contact']->set('firstname', _wp('Contact'));
                        $d['contact']->set('lastname', _wp('Removed'));

                        $this->updateByField('code', $d['code'], array('contact_id' => null)); // тут ок
                        $ccm->updateById($d['code'], array('contact_id' => null));
                        $d['contact_id'] = null;

                    }

                } else {
                    $d['contact'] = $ccm->getContactByCode($d['code']);
                }

                if(!empty($sources[$d['id']])) {
                    $d['source'] = $sources[$d['id']];
                }

                $items = $ccp->getByCode($d['code'], $d['storefront']);

                $d['total_price'] = $items['total'];
                $d['total_products'] = $items['total_quantity'];
                $d['total_skus'] = count($items['items']);
            }
        }

        $res = array();
        $res['items'] = $data;
        $res['total'] = $this->query('SELECT COUNT(*)'.$sql)->fetchField();
        return $res;
    }

    public function saveStorefront($code, $storefront, $contact_id, $total, $currency, $referer = null, $landing = null)
    {
        $data = array(
            'storefront' => $storefront,
            'contact_id' => $contact_id,
            'total' => $total,
            'currency' => $currency,
            'edit_datetime' => date('Y-m-d H:i:s'),
        );

        $e = $this->select('id, order_id')->where('code=?', $code)
            ->order('id DESC')
            ->fetchAssoc();

        if($e && !$e['order_id']) {
            $id = $e['id'];
            $this->updateById($id, $data);
        } else {
            $data['code'] = $code;
            $id = $this->insert($data);
        }

        if($id) {
            $referer_model = new shopCartsPluginStorefrontRefererModel();
            $referer_model->saveReferer($id, $referer, $landing);
        }

    }

    protected function getReportSql($params)
    {
        if(($params['timeframe'] == 'custom') && $params['from'] && $params['to']) {
            $from = date('Y-m-d 00:00:00', $params['from']);
            $to = date('Y-m-d 23:59:59', $params['to']);
            $where = 'edit_datetime BETWEEN \''.$from. '\' AND \''.$to."'";
        } elseif((int) $params['timeframe']) {
            $where = 'edit_datetime > (NOW() - interval '.((int) $params['timeframe']).' day)';
        } else {
            $where = '1';
        }

        $hash = $params['hash'];

        $sql = ' FROM ' . $this->getTableName() . ' s ';

        if($hash[0] == 'all') {
            $sql .= 'WHERE ' . $where . ' ';
        } elseif ($hash[0] == 'search') {
            if (!$query = ifempty($hash[1])) {
                $sql .= 'WHERE ' . $where . ' ';
            } else {
                $query = $this->escape($query);

                $conditions = array(
                    'c.contact LIKE "%'.$query.'%"',
                    'wc.name LIKE "%'.$query.'%"',
                    's.storefront LIKE "'.$query.'%"',
                    'wce.email LIKE "%'.$query.'%"',
                    's.code = "'.$query.'"',
                    's.id = "'.$query.'"',
                );

                if(preg_match('/^\d/', $query)) {
                    $digit = str_replace(',', '.', $query);
                    $digit = preg_replace('/[^\d.,]/', '', $digit);
                    $conditions[] = 'ROUND(s.total) = '.round($digit);
                }

                if($order_id = shopHelper::decodeOrderId($query)) {
                    $conditions[] = 's.order_id = '.$order_id;
                }

                // by product name
                $sql1 = 'SELECT i.code FROM shop_cart_items i '.
                'JOIN shop_product p ON i.product_id = p.id '.
                'JOIN shop_product_skus s ON i.sku_id = s.id '.
                'WHERE p.name LIKE "%'.$query.'%" OR  s.name LIKE "%'.$query.'%" OR s.sku LIKE "%'.$query.'%" '.
                'ORDER BY i.create_datetime DESC '.
                'LIMIT 30';
                if($codes = $this->query($sql1)->fetchAll(null, true)) {
                    $conditions[] = 's.code IN("'.implode('","', $codes).'")';
                }


                $sql .= 'LEFT JOIN shop_carts_plugin_contact c ON c.code = s.code ' .
                    'LEFT JOIN wa_contact wc ON wc.id = s.contact_id '.
                    'LEFT JOIN wa_contact_emails wce ON wc.id = wce.contact_id '.
                    'WHERE ' . $where . ' AND ('.implode(' OR ', $conditions).') ';
            }
        } else {
            $sql .= 'LEFT JOIN shop_carts_plugin_contact c ON c.code = s.code ' .
                'WHERE ' . $where . ' AND (c.contact_id IS NOT NULL OR s.contact_id IS NOT NULL OR c.contact IS NOT NULL) ';
        }

        return $sql;
    }
}