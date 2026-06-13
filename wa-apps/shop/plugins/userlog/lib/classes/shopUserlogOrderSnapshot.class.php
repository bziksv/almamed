<?php

class shopUserlogOrderSnapshot
{
    public static function captureForLog($order_id)
    {
        $order_id = (int) $order_id;
        if (!$order_id) {
            return null;
        }

        $order_model = new shopOrderModel();
        $order = $order_model->getById($order_id);
        if (!$order) {
            return null;
        }

        $params_model = new shopOrderParamsModel();
        $params = $params_model->get($order_id);

        $items_model = new shopOrderItemsModel();
        $items = array();
        foreach ($items_model->getItems($order_id) as $item) {
            if (is_array($item)) {
                $items[] = self::normalizeItemRow($item);
            }
        }

        return array(
            'order'       => self::trimOrder($order),
            'items'       => $items,
            'params'      => is_array($params) ? $params : array(),
            'captured_at' => date('Y-m-d H:i:s'),
        );
    }

    protected static function itemDbFields()
    {
        return array(
            'id', 'order_id', 'name', 'product_id', 'sku_id', 'sku_code', 'type',
            'service_id', 'service_variant_id', 'price', 'quantity', 'parent_id',
            'stock_id', 'virtualstock_id', 'purchase_price', 'total_discount',
            'tax_percent', 'tax_included',
        );
    }

    protected static function normalizeItemRow(array $item)
    {
        return array_intersect_key($item, array_flip(self::itemDbFields()));
    }

    protected static function trimOrder(array $order)
    {
        $keys = array(
            'id', 'contact_id', 'state_id', 'total', 'tax', 'shipping', 'discount',
            'currency', 'rate', 'tax_name', 'comment', 'paid_date', 'paid_year',
            'paid_quarter', 'paid_month', 'auth_date', 'is_first', 'unsettled',
        );
        return array_intersect_key($order, array_flip($keys));
    }

    protected static function trimItems(array $items)
    {
        $result = array();
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $result[] = self::normalizeItemRow($item);
        }
        return $result;
    }

    protected static function resolveStateName($state_id)
    {
        if ($state_id === '' || $state_id === null) {
            return '';
        }
        try {
            $workflow = new shopWorkflow();
            $state = $workflow->getStateById($state_id);
            return $state ? $state->getName() : (string) $state_id;
        } catch (Exception $e) {
            return (string) $state_id;
        }
    }

    protected static function flattenItems(array $items)
    {
        if (!$items) {
            return '';
        }
        $parts = array();
        foreach ($items as $item) {
            $name = ifset($item, 'name', 'Позиция');
            $qty = ifset($item, 'quantity', 0);
            $price = ifset($item, 'price', 0);
            $sku = ifset($item, 'sku_code', '');
            $line = $name.' × '.$qty.' @ '.$price;
            if ($sku) {
                $line .= ' ('.$sku.')';
            }
            $parts[] = $line;
        }
        return implode('; ', $parts);
    }

    public static function flattenForDiff(array $snapshot)
    {
        $order = ifset($snapshot, 'order', array());

        return array(
            'state'    => self::resolveStateName(ifset($order, 'state_id', '')),
            'total'    => ifset($order, 'total', null),
            'tax'      => ifset($order, 'tax', null),
            'shipping' => ifset($order, 'shipping', null),
            'discount' => ifset($order, 'discount', null),
            'currency' => ifset($order, 'currency', null),
            'comment'  => userlogHelper::plainTextForDisplay((string) ifset($order, 'comment', ''), 200),
            'items'    => self::flattenItems(ifset($snapshot, 'items', array())),
        );
    }

    public static function restoreState($order_id, $state_id)
    {
        $order_id = (int) $order_id;
        if (!$order_id || $state_id === '' || $state_id === null) {
            throw new waException('Нет данных для отката статуса заказа');
        }
        $model = new shopOrderModel();
        if (!$model->getById($order_id)) {
            throw new waException('Заказ не найден');
        }
        $model->updateById($order_id, array('state_id' => $state_id));
        return $order_id;
    }

    public static function restoreForUpdate(array $before, $order_id)
    {
        $order_id = (int) $order_id;
        $order_data = ifset($before, 'order', array());
        if (!$order_id || !$order_data) {
            throw new waException('Нет данных для отката заказа');
        }
        $model = new shopOrderModel();
        if (!$model->getById($order_id)) {
            throw new waException('Заказ не найден');
        }

        if (empty($before['items']) && !empty($order_data['total']) && (float) $order_data['total'] > 0) {
            throw new waException(
                'Не сохранён состав заказа для отката. Удалите позиции ещё раз после обновления страницы и повторите откат.'
            );
        }

        $fields = array(
            'contact_id', 'state_id', 'total', 'tax', 'shipping', 'discount',
            'currency', 'rate', 'tax_name', 'comment',
        );
        $update = array();
        foreach ($fields as $field) {
            if (array_key_exists($field, $order_data)) {
                $update[$field] = $order_data[$field];
            }
        }
        if ($update) {
            $model->updateById($order_id, $update);
        }
        if (array_key_exists('params', $before)) {
            (new shopOrderParamsModel())->set($order_id, (array) $before['params']);
        }
        if (!empty($before['items']) && is_array($before['items'])) {
            self::restoreItems($order_id, $before['items']);
        }
        return $order_id;
    }

    protected static function restoreItems($order_id, array $items)
    {
        $order_id = (int) $order_id;
        if (!$order_id || !$items) {
            return;
        }
        $prepared = self::prepareItemsForRestore($order_id, $items);
        if ($prepared) {
            (new shopOrderItemsModel())->update($prepared, $order_id);
        }
    }

    protected static function prepareItemsForRestore($order_id, array $items)
    {
        $sku_model = new shopProductSkusModel();
        $result = array();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = self::normalizeItemRow($item);
            $row['order_id'] = $order_id;

            if (!isset($row['type']) || $row['type'] === '') {
                $row['type'] = !empty($row['service_id']) ? 'service' : 'product';
            }
            if (!array_key_exists('parent_id', $row) || $row['parent_id'] === '') {
                $row['parent_id'] = null;
            }
            if (!array_key_exists('total_discount', $row) || $row['total_discount'] === '') {
                $row['total_discount'] = 0;
            }
            if (!array_key_exists('tax_included', $row) || $row['tax_included'] === '') {
                $row['tax_included'] = 0;
            }
            if ($row['type'] === 'product' && !empty($row['sku_id'])
                && (!array_key_exists('purchase_price', $row) || $row['purchase_price'] === '' || $row['purchase_price'] === null)
            ) {
                $sku = $sku_model->getById($row['sku_id']);
                if ($sku && array_key_exists('purchase_price', $sku)) {
                    $row['purchase_price'] = $sku['purchase_price'];
                }
            }

            $result[] = $row;
        }

        return $result;
    }
}
