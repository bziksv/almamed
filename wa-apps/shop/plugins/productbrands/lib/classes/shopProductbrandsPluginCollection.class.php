<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
class shopProductbrandsPluginCollection extends shopProductsCollection
{
    public function getBrands()
    {
        $feature_id = (int)wa()->getSetting('feature_id', '', array('shop', 'productbrands'));
        $alias = $this->addJoin('shop_product_features', null, ':table.feature_id = '.$feature_id.' AND :table.sku_id IS NULL');
        $sql = $this->getSQL();
        $sql = 'SELECT '.$alias.'.feature_value_id, COUNT(DISTINCT p.id) '.$sql.' GROUP BY '.$alias.'.feature_value_id';
        $rows = $this->getModel()->query($sql)->fetchAll('feature_value_id', true);
        $brands = shopProductbrandsPlugin::getBrands();
        foreach ($brands as $id => $b) {
            if (isset($rows[$id])) {
                $brands[$id]['count'] = $rows[$id];
            } else {
                unset($brands[$id]);
            }
        }
        return $brands;
    }

    public function getFeatures($id_index = false)
    {
        $alias = $this->addJoin('shop_product_features');

        $sql = $this->getSQL();
        $sql = 'SELECT DISTINCT '.$alias.'.feature_id, '.$alias.'.feature_value_id '.$sql;
        $rows = $this->getModel()->query($sql);
        if (!$rows) {
            return array();
        }
        $result = array();
        foreach ($rows as $row) {
            $result[$row['feature_id']][] = $row['feature_value_id'];
        }
        if ($id_index) {
            return $result;
        }
        if ($result) {
            $feature_model = new shopFeatureModel();
            $features = $feature_model->select('id, code')->where('id IN ('.implode(',', array_keys($result)).')')
                ->fetchAll('id', true);
            foreach ($result as $f_id => $v) {
                if (isset($features[$f_id])) {
                    $result[$features[$f_id]] = $v;
                }
                unset($result[$f_id]);
            }
        }
        return $result;
    }

    public function getMaxPrice()
    {
        $sql = $this->getSQL();
        $sql = "SELECT MAX(p.max_price) ".$sql;
        return (int) $this->getModel()->query($sql)->fetchField();
    }

    public function getMinPrice()
    {
        $where = $this->where;
        $this->where[] = 'p.min_price > 0';
        $sql = $this->getSQL();
        $this->where = $where;
        $sql = "SELECT MIN(p.min_price) ".$sql;
        return (int) $this->getModel()->query($sql)->fetchField();
    }

    public function filters($data, $force = false)
    {
        if ($force) {
            $this->filtered = false;
            if ($this->prepared) {
                $this->prepared = false;
            }
        }
        parent::filters($data);
    }

    public function setBrandSortProducts($sort)
    {
        $tmp = explode(' ', $sort);
        if (!isset($tmp[1])) {
            $tmp[1] = 'DESC';
        }
        if ($tmp[0] == 'count') {
            $this->fields[] = 'IF(p.count IS NULL, 1, 0) count_null';
            $this->order_by = 'count_null '.$tmp[1].', p.count '.$tmp[1];
        } else {
            $this->order_by = 'p.'.$sort;
        }
    }
}