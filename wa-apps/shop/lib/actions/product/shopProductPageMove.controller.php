<?php

class shopProductPageMoveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', null, waRequest::TYPE_INT);
        if (!$id) {
            throw new waException(_w("Unknown page"));
        }

        $before_id = waRequest::post('before_id', null, waRequest::TYPE_INT);
        if ($id == $before_id) {
            $this->errors[] = _w("Page couldn't be inserted before itself");
        }

        $product_page_model = new shopProductPagesModel();
        $page = $product_page_model->getById($id);
        if (!$page) {
            throw new waException(_w("Unknown page"));
        }

        $product_model = new shopProductModel();
        if (!$product_model->checkRights($page['product_id'])) {
            throw new waException(_w("Access denied"));
        }

        $sort_before = (int) ifset($page, 'sort', 0);
        if (!$product_page_model->move($id, $before_id)) {
            $this->errors[] = _w("Error when move");
            return;
        }

        $page_after = $product_page_model->getById($id);
        $plugin = shopUserlogPlugin::getInstance();
        if ($plugin && $page_after) {
            $plugin->logProductPageMove(
                $page,
                $sort_before,
                (int) ifset($page_after, 'sort', 0)
            );
        }
    }
}