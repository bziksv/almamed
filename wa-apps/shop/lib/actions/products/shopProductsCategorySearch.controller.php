<?php

class shopProductsCategorySearchController extends waJsonController
{
    public function execute()
    {
        $q = waRequest::get('q', '', waRequest::TYPE_STRING_TRIM);
        if (mb_strlen($q) < 2) {
            $this->response = array('categories' => array());
            return;
        }

        $model = new shopCategoryModel();
        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        $params = array('type' => shopCategoryModel::TYPE_STATIC);
        $where = array('type = i:type');
        foreach ($words as $i => $word) {
            $key = 'like'.$i;
            $where[] = 'name LIKE s:'.$key;
            $params[$key] = '%'.$model->escape($word, 'like').'%';
        }
        $categories = $model->query(
            "SELECT id, name FROM {$model->getTableName()}
            WHERE ".implode(' AND ', $where)."
            ORDER BY name
            LIMIT 20",
            $params
        )->fetchAll();

        $result = array();
        foreach ($categories as $category) {
            $path_names = array();
            $path_ids = array();
            foreach (array_reverse($model->getPath($category['id']), true) as $item) {
                $path_names[] = $item['name'];
                $path_ids[] = (int) $item['id'];
            }
            $path_names[] = $category['name'];
            $path_ids[] = (int) $category['id'];

            $result[] = array(
                'id'       => (int) $category['id'],
                'name'     => $category['name'],
                'path'     => implode(' › ', $path_names),
                'path_ids' => $path_ids,
            );
        }

        $this->response = array(
            'categories' => $result,
        );
    }
}
