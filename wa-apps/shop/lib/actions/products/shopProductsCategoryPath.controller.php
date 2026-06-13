<?php

class shopProductsCategoryPathController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::get('id', 0, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException(_w('Category not found'), 404);
        }

        $model = new shopCategoryModel();
        $category = $model->getById($id);
        if (!$category) {
            throw new waException(_w('Category not found'), 404);
        }

        $path = array();
        foreach (array_reverse($model->getPath($id), true) as $item) {
            $path[] = array(
                'id'   => (int) $item['id'],
                'name' => $item['name'],
            );
        }
        $path[] = array(
            'id'   => (int) $category['id'],
            'name' => $category['name'],
        );

        $this->response = array(
            'path' => $path,
        );
    }
}
