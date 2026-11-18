<?php

class shopSearchIndexCli extends waCliController
{
    public function execute()
    {
        $search = new shopIndexSearch();
        if (waRequest::param(0) === 'missing') {
            $this->indexMissing($search, new shopProductModel(), waRequest::param(1));
        } elseif (waRequest::param(0)) {
            $search->indexProduct(waRequest::param(0));
        } else {
            $product_model = new shopProductModel();
            $n = $product_model->countAll();
            $limit = 100;
            $i = 0;
            $product_model->exec("TRUNCATE TABLE shop_search_index");

            while ($i < $n) {
                echo $i."/".$n."\n";
                $sql = "SELECT p.*, t.name type_name FROM ".$product_model->getTableName()." p
                LEFT JOIN shop_type t ON p.type_id = t.id
                LIMIT ".$i.", ".$limit;
                $products = $product_model->query($sql)->fetchAll('id');
                $product_ids = array_keys($products);
                // get skus
                $sql = "SELECT * FROM shop_product_skus WHERE product_id IN (i:id)";
                $data = $product_model->query($sql, array('id' => $product_ids));
                foreach ($data as $row) {
                    $products[$row['product_id']]['skus'][] = $row;
                }
                // get tags
                $sql = "SELECT pt.product_id, t.name FROM shop_product_tags pt
                JOIN shop_tag t ON pt.tag_id = t.id WHERE pt.product_id IN (i:id)";
                $data = $product_model->query($sql, array('id' => $product_ids));
                foreach ($data as $row) {
                    $products[$row['product_id']]['tags'][] = $row['name'];
                }
                // get features
                $sql = "SELECT pf.product_id, fv.value FROM shop_product_features pf
                JOIN shop_feature f ON pf.feature_id = f.id AND f.type = 'varchar'
                JOIN shop_feature_values_varchar fv ON pf.feature_value_id = fv.id WHERE pf.product_id IN (i:id)";
                $data = $product_model->query($sql, array('id' => $product_ids));
                foreach ($data as $row) {
                    $products[$row['product_id']]['features'][] = $row['value'];
                }

                $sql = "SELECT pf.product_id, fv.value FROM shop_product_features pf
                JOIN shop_feature f ON pf.feature_id = f.id AND f.type = 'double'
                JOIN shop_feature_values_double fv ON pf.feature_value_id = fv.id WHERE pf.product_id IN (i:id)";
                $data = $product_model->query($sql, array('id' => $product_ids));
                foreach ($data as $row) {
                    $products[$row['product_id']]['features'][] = $row['value'];
                }

                $sql = "SELECT pf.product_id, fv.value FROM shop_product_features pf
                JOIN shop_feature f ON pf.feature_id = f.id AND f.type = 'text'
                JOIN shop_feature_values_text fv ON pf.feature_value_id = fv.id WHERE pf.product_id IN (i:id)";
                $data = $product_model->query($sql, array('id' => $product_ids));
                foreach ($data as $row) {
                    $products[$row['product_id']]['features'][] = $row['value'];
                }

                // get skus
                $sql = "SELECT * FROM shop_product_skus WHERE product_id IN (i:id)";
                $data = $product_model->query($sql, array('id' => $product_ids));
                foreach ($data as $row) {
                    $products[$row['product_id']]['skus'][] = $row;
                }
                foreach ($products as $p) {
                    $search->indexProduct($p, false);
                }
                $i += $limit;
            }
        }
    }

    /**
     * Индексирует активные товары, которых нет в shop_search_index.
     * Без TRUNCATE. Второй аргумент — подстрока названия (например kawe).
     */
    protected function indexMissing(shopIndexSearch $search, shopProductModel $product_model, $name_like = null)
    {
        $limit = 80;
        $done = 0;
        $name_like = $name_like ? trim($name_like) : '';

        while (true) {
            $sql = "SELECT p.id FROM shop_product p
                LEFT JOIN shop_search_index si ON si.product_id = p.id
                WHERE p.status = 1 AND si.product_id IS NULL";
            if ($name_like !== '') {
                $sql .= " AND p.name LIKE '%" . $product_model->escape($name_like, 'like') . "%'";
            }
            $sql .= " ORDER BY p.id LIMIT " . (int) $limit;
            $ids = $product_model->query($sql)->fetchAll(null, true);
            if (!$ids) {
                break;
            }
            foreach ($ids as $id) {
                $search->indexProduct((int) $id, true);
                $done++;
            }
            echo $done . "\n";
            if (count($ids) < $limit) {
                break;
            }
        }
        echo "indexed {$done}\n";
    }
}