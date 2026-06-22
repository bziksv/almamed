<?php

class shopCategoryfinderPluginListController extends waController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $duplicate_mode = waRequest::post('filter_duplicate', '', waRequest::TYPE_STRING);

        $service = new shopCategoryfinderService();
        $rows = $service->getList(array(
            'level' => waRequest::post('filter_level', 1, waRequest::TYPE_INT),
            'cnt' => waRequest::post('filter_cnt', '', waRequest::TYPE_STRING),
            'active' => waRequest::post('filter_active', '', waRequest::TYPE_STRING),
            'redirect' => waRequest::post('filter_redirect', '', waRequest::TYPE_STRING),
            'without_prod' => waRequest::post('filter_without_prod', '', waRequest::TYPE_STRING),
            'name' => waRequest::post('filter_name', '', waRequest::TYPE_STRING_TRIM),
            'storefront' => waRequest::post('filter_storefront', '', waRequest::TYPE_STRING),
            'duplicate' => $duplicate_mode,
            'duplicate_similarity' => waRequest::post('filter_duplicate_similarity', shopCategoryfinderService::DEFAULT_URL_SIMILARITY, waRequest::TYPE_INT),
        ));

        $data = array();
        $duplicate_group_count = 0;
        foreach ($rows as $i => $row) {
            $name = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
            $admin_url = htmlspecialchars($row['admin_url'], ENT_QUOTES, 'UTF-8');
            $public_url = htmlspecialchars($row['public_url'], ENT_QUOTES, 'UTF-8');
            $public_text = htmlspecialchars($row['public_url'], ENT_QUOTES, 'UTF-8');
            $url_slug = htmlspecialchars(ifset($row, 'url', ''), ENT_QUOTES, 'UTF-8');
            $storefront_label = htmlspecialchars(ifset($row, 'storefront_label', ''), ENT_QUOTES, 'UTF-8');
            $duplicate_group = (int) ifset($row, 'duplicate_group', 0);

            if ($duplicate_group > $duplicate_group_count) {
                $duplicate_group_count = $duplicate_group;
            }

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
                $this->renderDuplicateMatches(ifset($row, 'duplicate_matches', array())),
                !empty($row['without_prod']),
                $duplicate_group,
            );
        }

        wa()->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
        $total = count($data);
        echo json_encode(array(
            'draw' => waRequest::post('draw', 0, waRequest::TYPE_INT),
            'data' => $data,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'duplicateMode' => $duplicate_mode,
            'duplicateGroupCount' => $duplicate_group_count,
        ));
    }

    /**
     * @param array<int, array{id:int,reason:string}> $matches
     * @return string
     */
    protected function renderDuplicateMatches(array $matches)
    {
        if (!$matches) {
            return '';
        }

        $parts = array();
        foreach ($matches as $match) {
            $id = (int) ifset($match, 'id', 0);
            if (!$id) {
                continue;
            }
            $reason = htmlspecialchars(ifset($match, 'reason', ''), ENT_QUOTES, 'UTF-8');
            $parts[] = '<a href="#" class="cf-dup-link" data-cf-id="' . $id . '">' . $id . ' (' . $reason . ')</a>';
        }

        if (!$parts) {
            return '';
        }

        if (count($parts) > 5) {
            return implode(', ', array_slice($parts, 0, 5)) . '…';
        }

        return implode(', ', $parts);
    }
}
