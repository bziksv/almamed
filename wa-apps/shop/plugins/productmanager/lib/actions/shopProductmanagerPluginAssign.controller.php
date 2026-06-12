<?php

class shopProductmanagerPluginAssignController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $service = new shopProductmanagerService();
        $mode = waRequest::post('mode', 'assign', waRequest::TYPE_STRING_TRIM);

        $category_ids = waRequest::post('category_ids', array(), waRequest::TYPE_ARRAY_INT);
        $manager_ids = waRequest::post('manager_ids', array(), waRequest::TYPE_ARRAY_INT);
        $only_unassigned = (bool) waRequest::post('only_unassigned', 1, waRequest::TYPE_INT);
        $include_subcategories = true;

        if ($mode === 'bind') {
            $category_id = (int) waRequest::post('category_id', 0, waRequest::TYPE_INT);
            $manager_id = (int) waRequest::post('manager_id', 0, waRequest::TYPE_INT);

            if (!$category_id || !$manager_id) {
                throw new waException('Category and manager are required');
            }

            $result = $service->bindCategoryManager(
                $category_id,
                $manager_id,
                $include_subcategories
            );

            $this->response = $this->buildResponse($result['updated']);
            return;
        }

        if ($mode === 'set_manager') {
            $category_id = (int) waRequest::post('category_id', 0, waRequest::TYPE_INT);
            $manager_id = (int) waRequest::post('manager_id', 0, waRequest::TYPE_INT);

            if (!$category_id || !$manager_id) {
                throw new waException('Category and manager are required');
            }

            $result = $service->setCategoryManager(
                $category_id,
                $manager_id,
                $only_unassigned,
                $include_subcategories
            );

            $this->response = $this->buildResponse($result['updated']);
            return;
        }

        if ($mode === 'unbind') {
            $category_id = (int) waRequest::post('category_id', 0, waRequest::TYPE_INT);
            if (!$category_id) {
                throw new waException('Category is required');
            }

            $service->unbindCategoryManager($category_id, $include_subcategories);
            $this->response = $this->buildResponse(0);
            return;
        }

        if ($mode === 'clear') {
            $updated = $service->clearManagers($category_ids, $include_subcategories);
            $this->response = $this->buildResponse($updated);
            return;
        }

        if ($mode === 'save_pool') {
            $saved = $service->saveManagerPool($manager_ids);
            $this->response = array(
                'status' => 'ok',
                'manager_ids' => $saved,
            );
            return;
        }

        $result = $service->randomAssign(
            $category_ids,
            $manager_ids,
            $only_unassigned,
            $include_subcategories
        );

        $this->response = array_merge(
            $this->buildResponse($result['updated']),
            array('by_category' => $result['by_category'])
        );
    }

    protected function buildResponse($updated)
    {
        $service = new shopProductmanagerService();

        return array(
            'status' => 'ok',
            'updated' => $updated,
            'summary' => $service->getSummary(),
            'categories' => $service->getCategoryRows(false),
            'managers' => $service->getManagers(),
        );
    }
}
