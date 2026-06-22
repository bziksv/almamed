<?php

class shopCategoryfinderPluginListController extends waController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $service = new shopCategoryfinderService();
        $rows = $service->getList(array(
            'level' => waRequest::post('filter_level', 1, waRequest::TYPE_INT),
            'cnt' => waRequest::post('filter_cnt', '', waRequest::TYPE_STRING),
            'active' => waRequest::post('filter_active', '', waRequest::TYPE_STRING),
            'redirect' => waRequest::post('filter_redirect', '', waRequest::TYPE_STRING),
            'without_prod' => waRequest::post('filter_without_prod', '', waRequest::TYPE_STRING),
            'storefront' => waRequest::post('filter_storefront', '', waRequest::TYPE_STRING),
            'duplicate' => waRequest::post('filter_duplicate', '', waRequest::TYPE_STRING),
            'duplicate_similarity' => waRequest::post('filter_duplicate_similarity', shopCategoryfinderService::DEFAULT_URL_SIMILARITY, waRequest::TYPE_INT),
        ));

        $data = array();
        foreach ($rows as $i => $row) {
            $name = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
            $admin_url = htmlspecialchars($row['admin_url'], ENT_QUOTES, 'UTF-8');
            $public_url = htmlspecialchars($row['public_url'], ENT_QUOTES, 'UTF-8');
            $public_text = htmlspecialchars($row['public_url'], ENT_QUOTES, 'UTF-8');

            $duplicate_label = htmlspecialchars(ifset($row, 'duplicate_label', ''), ENT_QUOTES, 'UTF-8');
            $url_slug = htmlspecialchars(ifset($row, 'url', ''), ENT_QUOTES, 'UTF-8');
            $storefront_label = htmlspecialchars(ifset($row, 'storefront_label', ''), ENT_QUOTES, 'UTF-8');

            $data[] = array(
                $i + 1,
                $row['depth'],
                $row['id'],
                $row['count'],
                !empty($row['include_sub_categories']) ? 'Да' : 'Нет',
                (int) ifset($row, 'subtree_count', 0),
                $storefront_label,
                $row['status'] > 0 ? 'Да' : 'Нет',
                '<a href="' . $admin_url . '" target="_blank" rel="noopener">' . $name . '</a>',
                $url_slug,
                '<a href="' . $public_url . '" target="_blank" rel="noopener">' . $public_text . '</a>',
                $duplicate_label,
                !empty($row['without_prod']),
            );
        }

        wa()->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
        $total = count($data);
        echo json_encode(array(
            'draw' => waRequest::post('draw', 0, waRequest::TYPE_INT),
            'data' => $data,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
        ));
    }
}
