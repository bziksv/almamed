<?php

/**
 * Direct child categories by parent_id (safe for delete/expand; ignores broken nested-set HTML).
 */
class shopProductsCategoryChildrenController extends waJsonController
{
    public function execute()
    {
        $parent_id = waRequest::get('id', 0, waRequest::TYPE_INT);
        $recursive = waRequest::get('recursive', 1, waRequest::TYPE_INT);

        $this->response = array(
            'ids' => self::collectChildIds($parent_id, (bool) $recursive),
        );
    }

    /**
     * @return int[]
     */
    public static function collectChildIds($parent_id, $recursive = true)
    {
        $parent_id = (int) $parent_id;
        $model = new shopCategoryModel();
        $ids = array();
        $queue = array($parent_id);

        while ($queue) {
            $pid = array_shift($queue);
            foreach ($model->getByField('parent_id', $pid, true) as $row) {
                $id = (int) $row['id'];
                $ids[] = $id;
                if ($recursive) {
                    $queue[] = $id;
                }
            }
        }

        return $ids;
    }
}
