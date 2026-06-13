<?php

class shopUserlogCategorySnapshot
{
    public static function capture($category_id)
    {
        $category_id = (int) $category_id;
        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        if (!$category) {
            return null;
        }

        $cp_model = new shopCategoryProductsModel();
        $products = $cp_model->getByField('category_id', $category_id, true);

        $params_model = new shopCategoryParamsModel();
        $params = $params_model->get($category_id);

        return array(
            'category'   => $category,
            'products'   => $products,
            'params'     => $params,
            'captured_at'=> date('Y-m-d H:i:s'),
        );
    }

    public static function restore(array $snapshot, $original_id)
    {
        wa('shop');
        $category_data = ifset($snapshot, 'category', array());
        if (!$category_data) {
            throw new waException('Пустой снимок категории');
        }

        $category_model = new shopCategoryModel();
        if ($category_model->getById($original_id)) {
            throw new waException('Категория с ID '.$original_id.' уже существует');
        }

        $category_data['id'] = $original_id;
        $category_model->insert($category_data, 1);

        if (!empty($snapshot['params'])) {
            (new shopCategoryParamsModel())->set($original_id, $snapshot['params']);
        }

        if (!empty($snapshot['products'])) {
            $cp_model = new shopCategoryProductsModel();
            foreach ($snapshot['products'] as $row) {
                $cp_model->insert(array(
                    'category_id' => $original_id,
                    'product_id'  => $row['product_id'],
                    'sort'        => ifset($row, 'sort', 0),
                ), 2);
            }
        }

        shopCategories::clear($original_id);

        $category_model->repair();

        return $original_id;
    }
}
