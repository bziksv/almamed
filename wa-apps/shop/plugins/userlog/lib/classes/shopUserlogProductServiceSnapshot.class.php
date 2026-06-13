<?php

class shopUserlogProductServiceSnapshot
{
    public static function captureForLog($product_id, $service_id)
    {
        $product_id = (int) $product_id;
        $service_id = (int) $service_id;
        if (!$product_id || !$service_id) {
            return null;
        }
        $service = (new shopServiceModel())->getById($service_id);
        if (!$service) {
            return null;
        }
        $info = (new shopProductServicesModel())->getProductServiceFullInfo($product_id, $service_id);
        return array(
            'service_id'   => $service_id,
            'service_name' => ifset($service, 'name', ''),
            'info'         => $info,
        );
    }

    public static function flattenForDiff(array $snapshot)
    {
        $flat = array(
            'service' => ifset($snapshot, 'service_name', ''),
        );
        $info = ifset($snapshot, 'info', array());
        foreach (ifset($info, 'variants', array()) as $variant_id => $variant) {
            $status = self::formatStatus(ifset($variant, 'status', null));
            $price = ifset($variant, 'price', '');
            if ($price === null || $price === '') {
                $price = '—';
            }
            $flat['variant_'.$variant_id] = $status.' / '.$price;
        }
        return $flat;
    }

    protected static function formatStatus($status)
    {
        if ((int) $status === shopProductServicesModel::STATUS_DEFAULT) {
            return 'по умолчанию';
        }
        if ((int) $status === shopProductServicesModel::STATUS_PERMITTED) {
            return 'разрешена';
        }
        if ((int) $status === shopProductServicesModel::STATUS_FORBIDDEN) {
            return 'запрещена';
        }
        return (string) $status;
    }
}
