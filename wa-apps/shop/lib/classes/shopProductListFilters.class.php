<?php

/**
 * Backend product list filters (SKU, name, badge, brand, visibility).
 */
class shopProductListFilters
{
    const PARAM_SKU    = 'filter_sku';
    const PARAM_NAME   = 'filter_name';
    const PARAM_BADGE  = 'filter_badge';
    const PARAM_BRAND  = 'filter_brand';
    const PARAM_STATUS = 'filter_status';

    public static function getFromRequest()
    {
        return array(
            'sku'    => self::normalizeFilterInput(waRequest::request(self::PARAM_SKU, '', waRequest::TYPE_STRING_TRIM)),
            'name'   => self::normalizeFilterInput(waRequest::request(self::PARAM_NAME, '', waRequest::TYPE_STRING_TRIM)),
            'badges' => self::parseBadgesFromRequest(),
            'brand'  => waRequest::request(self::PARAM_BRAND, 0, waRequest::TYPE_INT),
            'status' => self::normalizeStatusFilter(waRequest::request(self::PARAM_STATUS, '', waRequest::TYPE_STRING_TRIM)),
        );
    }

    /**
     * @return string '' | '0' | '1'
     */
    protected static function normalizeStatusFilter($value)
    {
        $value = (string) $value;
        if ($value === '0' || $value === '1') {
            return $value;
        }
        if ($value === 'hidden') {
            return '0';
        }
        if ($value === 'visible') {
            return '1';
        }
        return '';
    }

    protected static function normalizeFilterInput($value)
    {
        $value = (string) $value;
        $i = 0;
        while ($value !== '' && preg_match('/%[0-9A-Fa-f]{2}/', $value) && $i < 3) {
            $decoded = rawurldecode($value);
            if ($decoded === $value) {
                break;
            }
            $value = $decoded;
            $i++;
        }
        return $value;
    }

    protected static function parseBadgesFromRequest()
    {
        $badges = waRequest::request(self::PARAM_BADGE, array(), waRequest::TYPE_ARRAY_TRIM);
        if ($badges) {
            return self::normalizeBadgeKeys(array_values(array_filter($badges, 'strlen')));
        }

        $raw = waRequest::request(self::PARAM_BADGE, '', waRequest::TYPE_STRING_TRIM);
        $raw = self::normalizeFilterInput($raw);
        if ($raw === '') {
            return array();
        }

        if (strpos($raw, '|') !== false) {
            return self::normalizeBadgeKeys(array_values(array_filter(explode('|', $raw), 'strlen')));
        }

        return self::normalizeBadgeKeys(array($raw));
    }

    protected static function normalizeBadgeKeys(array $keys)
    {
        $result = array();
        foreach ($keys as $key) {
            $key = self::normalizeFilterInput($key);
            if ($key === '') {
                continue;
            }
            if (strpos($key, '|') !== false) {
                foreach (explode('|', $key) as $part) {
                    $part = self::normalizeFilterInput($part);
                    if ($part !== '') {
                        $result[] = $part;
                    }
                }
            } else {
                $result[] = $key;
            }
        }
        return array_values(array_unique($result));
    }

    public static function isActive(array $filters)
    {
        return !empty($filters['sku'])
            || !empty($filters['name'])
            || !empty($filters['badges'])
            || !empty($filters['brand'])
            || (isset($filters['status']) && $filters['status'] !== '');
    }

    /**
     * Whether a badge dropdown option (possibly grouped as key1|key2) is selected.
     */
    public static function isBadgeOptionSelected($option_key, array $selected_keys)
    {
        $parts = self::normalizeBadgeKeys(array($option_key));
        if (!$parts) {
            return false;
        }
        foreach ($parts as $part) {
            if (!in_array($part, $selected_keys, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string[] Grouped option keys that match current selection
     */
    public static function getSelectedBadgeOptionKeys(array $options, array $selected_keys)
    {
        $selected = array();
        foreach ($options as $option_key => $label) {
            if (self::isBadgeOptionSelected($option_key, $selected_keys)) {
                $selected[] = $option_key;
            }
        }
        return $selected;
    }

    public static function apply(shopProductsCollection $collection, array $filters)
    {
        if (!self::isActive($filters)) {
            return;
        }

        $model = new waModel();

        if ($filters['name'] !== '') {
            $collection->addWhere("p.name LIKE '%".$model->escape($filters['name'], 'like')."%'");
        }

        if ($filters['sku'] !== '') {
            $sku_alias = $collection->addJoin('shop_product_skus', ':table.product_id = p.id');
            $collection->addWhere($sku_alias.".sku LIKE '%".$model->escape($filters['sku'], 'like')."%'");
        }

        if (!empty($filters['badges'])) {
            $standard = shopProductModel::badges();
            $conditions = array();
            foreach ($filters['badges'] as $badge_key) {
                $condition = self::badgeFilterCondition($badge_key, $standard, $model);
                if ($condition) {
                    $conditions[] = $condition;
                }
            }
            if ($conditions) {
                $collection->addWhere('('.implode(' OR ', $conditions).')');
            }
        }

        if ($filters['brand'] > 0) {
            $feature = self::getBrandFeature();
            if ($feature) {
                $collection->addJoin(array(
                    'table' => 'shop_product_features',
                    'on'    => 'p.id = :table.product_id AND :table.feature_id = '.(int) $feature['id']
                        .' AND :table.feature_value_id = '.(int) $filters['brand']
                        .' AND :table.sku_id IS NULL',
                ));
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $collection->addWhere('p.status = '.(int) $filters['status']);
        }
    }

    /**
     * @param shopProductsCollection $collection Unfiltered collection for current list context
     */
    public static function getOptions(shopProductsCollection $collection)
    {
        $model = new waModel();

        $badges = array();
        try {
            $from = $collection->getSQL();
            $badge_where = (stripos($from, 'WHERE') !== false)
                ? $from.' AND p.badge IS NOT NULL AND p.badge != \'\''
                : $from.' WHERE p.badge IS NOT NULL AND p.badge != \'\'';
            $rows = $model->query(
                "SELECT DISTINCT p.badge AS badge ".$badge_where
            )->fetchAll();
            $standard = shopProductModel::badges();
            $grouped = array();
            foreach ($rows as $row) {
                $html = $row['badge'];
                $matched = self::matchStandardBadge($html, $standard);
                if ($matched) {
                    $key = 's:'.$matched['id'];
                    $label = $matched['name'];
                } elseif (isset($standard[$html])) {
                    $key = 's:'.$html;
                    $label = $standard[$html]['name'];
                } else {
                    $key = 'c:'.self::encodeBadgePayload($html);
                    $label = self::extractBadgeLabel($html);
                }
                if (!isset($grouped[$label])) {
                    $grouped[$label] = array();
                }
                $grouped[$label][$key] = $key;
            }
            foreach ($grouped as $label => $keys) {
                $badges[implode('|', array_keys($keys))] = $label;
            }
            asort($badges, SORT_NATURAL | SORT_FLAG_CASE);
        } catch (Exception $e) {
        }

        $brands = array();
        $feature = self::getBrandFeature();
        if ($feature) {
            try {
                $from = $collection->getSQL();
                $tail = preg_replace('/^FROM\s+shop_product\s+p\s*/i', '', $from);
                $value_ids = $model->query(
                    "SELECT DISTINCT pf.feature_value_id AS id
                     FROM shop_product p
                     INNER JOIN shop_product_features pf
                        ON pf.product_id = p.id
                       AND pf.feature_id = ".(int) $feature['id']."
                       AND pf.sku_id IS NULL
                     ".$tail
                )->fetchAll(null, true);
                if ($value_ids) {
                    $values_model = (new shopFeatureModel())->getValuesModel($feature['type']);
                    $feature_values = $values_model->getValues('id', $value_ids);
                    $feature_id = (int) $feature['id'];
                    if (!empty($feature_values[$feature_id]) && is_array($feature_values[$feature_id])) {
                        $brands = $feature_values[$feature_id];
                        asort($brands, SORT_NATURAL | SORT_FLAG_CASE);
                    }
                }
            } catch (Exception $e) {
            }
        }

        return array(
            'badges' => $badges,
            'brands' => $brands,
        );
    }

    public static function buildUrlParams(array $filters)
    {
        $parts = array();
        if ($filters['sku'] !== '') {
            $parts[] = self::PARAM_SKU.'='.rawurlencode($filters['sku']);
        }
        if ($filters['name'] !== '') {
            $parts[] = self::PARAM_NAME.'='.rawurlencode($filters['name']);
        }
        if (!empty($filters['badges'])) {
            $parts[] = self::PARAM_BADGE.'='.rawurlencode(implode('|', $filters['badges']));
        }
        if ($filters['brand'] > 0) {
            $parts[] = self::PARAM_BRAND.'='.$filters['brand'];
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $parts[] = self::PARAM_STATUS.'='.$filters['status'];
        }
        return implode('&', $parts);
    }

    protected static function getBrandFeature()
    {
        static $feature = null;
        static $resolved = false;
        if ($resolved) {
            return $feature;
        }
        $resolved = true;

        $feature_model = new shopFeatureModel();
        $feature_id = wa()->getSetting('feature_id', null, array('shop', 'productbrands'));
        if ($feature_id) {
            $feature = $feature_model->getById($feature_id) ?: null;
            return $feature;
        }

        foreach (array('brand', 'manufacturer', 'brend', 'proizvoditel') as $code) {
            $found = $feature_model->getByCode($code);
            if ($found) {
                $feature = $found;
                return $feature;
            }
        }

        $feature = null;
        return $feature;
    }

    protected static function matchStandardBadge($html, array $standard)
    {
        if (isset($standard[$html])) {
            return array('id' => $html, 'name' => $standard[$html]['name']);
        }
        foreach ($standard as $id => $badge) {
            if ($html === $badge['code'] || strpos($html, 'badge '.$id) !== false) {
                return array('id' => $id, 'name' => $badge['name']);
            }
        }
        return null;
    }

    protected static function encodeBadgePayload($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected static function decodeBadgePayload($payload)
    {
        if ($payload === '') {
            return null;
        }
        $b64 = strtr($payload, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $decoded = base64_decode($b64, true);
        return $decoded === false ? null : $decoded;
    }

    protected static function badgeFilterCondition($badge_key, array $standard, waModel $model)
    {
        if (strpos($badge_key, 's:') === 0) {
            $id = substr($badge_key, 2);
            if (!isset($standard[$id])) {
                return null;
            }
            return "(p.badge LIKE '%".$model->escape('badge '.$id, 'like')."%'"
                ." OR p.badge = '".$model->escape($id)."')";
        }
        if (strpos($badge_key, 'c:') === 0) {
            $html = self::decodeBadgePayload(substr($badge_key, 2));
            if ($html === null) {
                return null;
            }
            return "p.badge = '".$model->escape($html)."'";
        }

        if (isset($standard[$badge_key])) {
            $id = $badge_key;
            return "(p.badge LIKE '%".$model->escape('badge '.$id, 'like')."%'"
                ." OR p.badge = '".$model->escape($id)."')";
        }
        $matched = self::matchStandardBadge($badge_key, $standard);
        if ($matched) {
            $id = $matched['id'];
            return "(p.badge LIKE '%".$model->escape('badge '.$id, 'like')."%'"
                ." OR p.badge = '".$model->escape($id)."')";
        }
        return "p.badge = '".$model->escape($badge_key)."'";
    }

    protected static function extractBadgeLabel($html)
    {
        if (preg_match('/<span[^>]*>(.*?)<\/span>/uis', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        $text = trim(strip_tags($html));
        return $text !== '' ? $text : _w('Custom');
    }
}
