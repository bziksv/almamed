<?php

class shopProductmanagerService
{
    /** @var shopProductmanagerPlugin */
    protected $plugin;

    public function __construct()
    {
        $this->plugin = wa('shop')->getPlugin('productmanager');
    }

    /**
     * Backend users from configured Webasyst group(s).
     *
     * @return array<int, array>
     */
    public function getManagers()
    {
        $managers = array();
        $user_ids = $this->getManagerUserIds();

        foreach ($user_ids as $id) {
            $managers[$id] = $this->plugin->get_user($id);
            $managers[$id]['id'] = $id;
            if (empty($managers[$id]['name'])) {
                $managers[$id]['name'] = (new waContact($id))->getName();
            }
            $managers[$id]['assigned_count'] = 0;
        }

        foreach ((new waModel())->query(
            "SELECT manager, COUNT(*) AS cnt
             FROM shop_product
             WHERE status >= 0 AND manager IS NOT NULL AND manager > 0
             GROUP BY manager"
        ) as $row) {
            $mid = (int) $row['manager'];
            if (isset($managers[$mid])) {
                $managers[$mid]['assigned_count'] = (int) $row['cnt'];
            }
        }

        uasort($managers, function ($a, $b) {
            return strcasecmp(ifset($a, 'name', ''), ifset($b, 'name', ''));
        });

        return $managers;
    }

    /**
     * @return string[]
     */
    public function getManagerGroupNames()
    {
        $settings = $this->plugin->getSettings();
        $raw = trim((string) ifset($settings, 'manager_group', 'Менеджеры по продажам'));
        if ($raw === '') {
            $raw = 'Менеджеры по продажам';
        }

        $names = preg_split('/[\n,;]+/u', $raw);
        $names = array_values(array_filter(array_map('trim', $names)));

        return $names ?: array('Менеджеры по продажам');
    }

    /**
     * @return int[]
     */
    public function getManagerGroupIds()
    {
        $names = $this->getManagerGroupNames();
        $all_groups = (new waGroupModel())->getNames();
        $names_lower = array();
        foreach ($names as $name) {
            $names_lower[mb_strtolower($name, 'UTF-8')] = true;
        }

        $ids = array();
        foreach ($all_groups as $id => $group_name) {
            if (isset($names_lower[mb_strtolower($group_name, 'UTF-8')])) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return int[]
     */
    public function getManagerUserIds()
    {
        $group_ids = $this->getManagerGroupIds();
        if (!$group_ids) {
            return array();
        }

        $user_groups_model = new waUserGroupsModel();
        $user_ids = array();
        foreach ($group_ids as $group_id) {
            $user_ids = array_merge($user_ids, $user_groups_model->getContactIds($group_id));
        }

        $user_ids = array_values(array_unique(array_map('intval', $user_ids)));
        if (!$user_ids) {
            return array();
        }

        $rows = (new waContactModel())->select('id')
            ->where('id IN (i:ids) AND is_user = 1', array('ids' => $user_ids))
            ->fetchAll(null, true);

        return array_map('intval', $rows);
    }

    public function isAllowedManagerId($manager_id)
    {
        $manager_id = (int) $manager_id;
        if (!$manager_id) {
            return false;
        }

        return in_array($manager_id, $this->getManagerUserIds(), true);
    }

    /**
     * @return int[]|null null = all managers from group are enabled
     */
    public function getManagerPoolIds()
    {
        $raw = trim((string) $this->plugin->getSettings('manager_pool'));
        if ($raw === '') {
            return null;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)))));
        $allowed = $this->getManagerUserIds();

        return array_values(array_intersect($ids, $allowed));
    }

    /**
     * @param int[] $manager_ids
     * @return int[]
     */
    public function saveManagerPool(array $manager_ids)
    {
        $allowed = $this->getManagerUserIds();
        $manager_ids = array_values(array_unique(array_filter(array_map('intval', $manager_ids))));
        $manager_ids = array_values(array_intersect($manager_ids, $allowed));

        $this->plugin->saveSettings(array(
            'manager_pool' => implode(',', $manager_ids),
        ));

        return $manager_ids;
    }

    public function isManagerInPool($manager_id)
    {
        $pool = $this->getManagerPoolIds();
        if ($pool === null) {
            return true;
        }

        return in_array((int) $manager_id, $pool, true);
    }

    /**
     * @return array{total:int,assigned:int,unassigned:int,categories_with_gaps:int}
     */
    public function getSummary()
    {
        $row = (new waModel())->query(
            "SELECT
                COUNT(*) AS total,
                SUM(IF(manager IS NOT NULL AND manager > 0, 1, 0)) AS assigned,
                SUM(IF(manager IS NULL OR manager = 0, 1, 0)) AS unassigned
             FROM shop_product
             WHERE status >= 0"
        )->fetchAssoc();

        $categories_with_gaps = (int) (new waModel())->query(
            "SELECT COUNT(DISTINCT category_id) FROM shop_product
             WHERE status >= 0 AND category_id > 0 AND (manager IS NULL OR manager = 0)"
        )->fetchField();

        return array(
            'total' => (int) ifset($row, 'total', 0),
            'assigned' => (int) ifset($row, 'assigned', 0),
            'unassigned' => (int) ifset($row, 'unassigned', 0),
            'categories_with_gaps' => $categories_with_gaps,
        );
    }

    /**
     * @return array<int, array>
     */
    public function getCategoryRows($hide_empty = false)
    {
        $category_model = new shopCategoryModel();
        $categories = $category_model->getFullTree('id, parent_id, depth, name, full_url, count, status');
        $bindings = (new shopProductmanagerCategoryModel())->getAllBindings();

        $totals = $this->fetchGroupedCounts(
            "SELECT category_id,
                    COUNT(*) AS total,
                    SUM(IF(manager IS NULL OR manager = 0, 1, 0)) AS unassigned
             FROM shop_product
             WHERE status >= 0 AND category_id > 0
             GROUP BY category_id"
        );

        $by_manager = array();
        foreach ((new waModel())->query(
            "SELECT category_id, manager, COUNT(*) AS cnt
             FROM shop_product
             WHERE status >= 0 AND category_id > 0 AND manager IS NOT NULL AND manager > 0
             GROUP BY category_id, manager"
        ) as $row) {
            $cid = (int) $row['category_id'];
            $mid = (int) $row['manager'];
            if (!isset($by_manager[$cid])) {
                $by_manager[$cid] = array();
            }
            $by_manager[$cid][$mid] = (int) $row['cnt'];
        }

        $rows = array();
        foreach ($categories as $id => $cat) {
            if ((int) ifset($cat, 'status', 1) < 0) {
                continue;
            }
            if ((int) ifset($cat, 'parent_id', 0) !== 0) {
                continue;
            }

            $subtree_ids = $this->getSubtreeCategoryIds((int) $id);
            $agg = $this->aggregateSubtreeStats($subtree_ids, $totals, $by_manager);

            if ($hide_empty && $agg['total'] === 0) {
                continue;
            }

            $rows[] = array(
                'id' => (int) $id,
                'parent_id' => 0,
                'name' => $cat['name'],
                'depth' => 0,
                'full_url' => ifset($cat, 'full_url', ''),
                'catalog_count' => (int) ifset($cat, 'count', 0),
                'total' => $agg['total'],
                'unassigned' => $agg['unassigned'],
                'assigned' => $agg['assigned'],
                'managers' => $agg['managers'],
                'bound_manager' => (int) ifset($bindings, $id, 0),
            );
        }

        return $rows;
    }

    /**
     * @return int[]
     */
    protected function getSubtreeCategoryIds($category_id)
    {
        $category_id = (int) $category_id;
        $rows = (new shopCategoryModel())
            ->descendants($category_id, true)
            ->fetchAll('id');

        if (!$rows) {
            return array($category_id);
        }

        return array_map('intval', array_keys($rows));
    }

    /**
     * @param int[] $category_ids
     * @return array{total:int,unassigned:int,assigned:int,managers:array}
     */
    protected function aggregateSubtreeStats(array $category_ids, array $totals, array $by_manager)
    {
        $total = 0;
        $unassigned = 0;
        $managers = array();

        foreach ($category_ids as $cid) {
            $stat = ifset($totals, $cid, array('total' => 0, 'unassigned' => 0));
            $total += (int) ifset($stat, 'total', 0);
            $unassigned += (int) ifset($stat, 'unassigned', 0);

            foreach (ifset($by_manager, $cid, array()) as $mid => $cnt) {
                $managers[$mid] = ifset($managers, $mid, 0) + (int) $cnt;
            }
        }

        return array(
            'total' => $total,
            'unassigned' => $unassigned,
            'assigned' => max(0, $total - $unassigned),
            'managers' => $managers,
        );
    }

    /**
     * @deprecated tree removed from admin UI
     */
    protected function normalizeCategoryTreeMeta(array $rows)
    {
        $ids = array();
        foreach ($rows as $row) {
            $ids[$row['id']] = true;
        }

        $children_count = array();
        foreach ($rows as $row) {
            $parent_id = (int) ifset($row, 'parent_id', 0);
            if ($parent_id > 0 && isset($ids[$parent_id])) {
                if (!isset($children_count[$parent_id])) {
                    $children_count[$parent_id] = 0;
                }
                $children_count[$parent_id]++;
            }
        }

        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['children_count'] = (int) ifset($children_count, $id, 0);
            $row['has_children'] = $row['children_count'] > 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * Find bound manager for category (walks up the tree).
     *
     * @param int $category_id
     * @return int
     */
    public function resolveBoundManager($category_id)
    {
        $category_id = (int) $category_id;
        if ($category_id <= 0) {
            return 0;
        }

        $bindings = (new shopProductmanagerCategoryModel())->getAllBindings();
        if (!$bindings) {
            return 0;
        }

        $category_model = new shopCategoryModel();
        $current = $category_id;
        $guard = 0;

        while ($current && $guard++ < 100) {
            if (!empty($bindings[$current])) {
                return (int) $bindings[$current];
            }
            $cat = $category_model->getById($current);
            if (!$cat) {
                break;
            }
            $current = (int) ifset($cat, 'parent_id', 0);
        }

        return 0;
    }

    /**
     * Assign selected manager to category products (without binding).
     *
     * @return array{updated:int}
     */
    public function setCategoryManager($category_id, $manager_id, $only_unassigned = true, $include_subcategories = false)
    {
        $category_id = (int) $category_id;
        $manager_id = (int) $manager_id;

        if (!$category_id || !$manager_id) {
            return array('updated' => 0);
        }

        if (!$this->isAllowedManagerId($manager_id)) {
            throw new waException('Manager is not in the configured group');
        }

        return array(
            'updated' => $this->assignManagerToCategoryProducts(
                $category_id,
                $manager_id,
                $include_subcategories,
                $only_unassigned
            ),
        );
    }

    /**
     * @return array{updated:int}
     */
    public function bindCategoryManager($category_id, $manager_id, $include_subcategories = false)
    {
        $category_id = (int) $category_id;
        $manager_id = (int) $manager_id;

        if (!$category_id || !$manager_id) {
            return array('updated' => 0);
        }

        if (!$this->isAllowedManagerId($manager_id)) {
            throw new waException('Manager is not in the configured group');
        }

        (new shopProductmanagerCategoryModel())->setBinding($category_id, $manager_id);

        return array(
            'updated' => $this->assignManagerToCategoryProducts(
                $category_id,
                $manager_id,
                $include_subcategories,
                false
            ),
        );
    }

    /**
     * @return array{updated:int}
     */
    public function unbindCategoryManager($category_id, $include_subcategories = false)
    {
        $category_id = (int) $category_id;
        if (!$category_id) {
            return array('updated' => 0);
        }

        (new shopProductmanagerCategoryModel())->removeBinding($category_id);

        return array('updated' => 0);
    }

    /**
     * @param int $category_id
     * @param int $manager_id
     * @param bool $include_subcategories
     * @param bool $only_unassigned
     * @return int
     */
    public function assignManagerToCategoryProducts($category_id, $manager_id, $include_subcategories = false, $only_unassigned = false)
    {
        $product_ids = $this->getProductIdsForCategory(
            (int) $category_id,
            $only_unassigned,
            $include_subcategories
        );

        if (!$product_ids) {
            return 0;
        }

        $product_model = new shopProductModel();
        foreach ($product_ids as $product_id) {
            $product_model->updateById($product_id, array('manager' => (int) $manager_id));
        }

        return count($product_ids);
    }

    /**
     * @param int|int[] $category_ids
     * @param int[] $manager_ids
     * @param bool $only_unassigned
     * @param bool $include_subcategories
     * @return array{updated:int, by_category:array}
     */
    public function randomAssign($category_ids, array $manager_ids, $only_unassigned = true, $include_subcategories = true)
    {
        $category_ids = array_values(array_filter(array_map('intval', (array) $category_ids)));
        $manager_ids = array_values(array_filter(array_map('intval', $manager_ids)));

        if (!$category_ids || !$manager_ids) {
            return array('updated' => 0, 'by_category' => array());
        }

        $manager_ids = array_values(array_filter($manager_ids, array($this, 'isAllowedManagerId')));
        if (!$manager_ids) {
            return array('updated' => 0, 'by_category' => array());
        }

        $product_model = new shopProductModel();
        $updated = 0;
        $by_category = array();

        foreach ($category_ids as $category_id) {
            $product_ids = $this->getProductIdsForCategory(
                $category_id,
                $only_unassigned,
                $include_subcategories
            );

            if (!$product_ids) {
                $by_category[$category_id] = 0;
                continue;
            }

            shuffle($product_ids);
            $count_managers = count($manager_ids);
            $cat_updated = 0;

            foreach ($product_ids as $product_id) {
                $manager_id = $manager_ids[array_rand($manager_ids)];
                $product_model->updateById($product_id, array('manager' => $manager_id));
                $cat_updated++;
            }

            $by_category[$category_id] = $cat_updated;
            $updated += $cat_updated;
        }

        return array(
            'updated' => $updated,
            'by_category' => $by_category,
        );
    }

    /**
     * @param int|int[] $category_ids
     * @return int
     */
    public function clearManagers($category_ids, $include_subcategories = true)
    {
        $product_ids = array();
        foreach ((array) $category_ids as $category_id) {
            $product_ids = array_merge(
                $product_ids,
                $this->getProductIdsForCategory((int) $category_id, false, $include_subcategories)
            );
        }
        $product_ids = array_values(array_unique(array_filter($product_ids)));

        if (!$product_ids) {
            return 0;
        }

        $product_model = new shopProductModel();
        foreach ($product_ids as $product_id) {
            $product_model->updateById($product_id, array('manager' => null));
        }

        return count($product_ids);
    }

    /**
     * @return int[]
     */
    protected function getProductIdsForCategory($category_id, $only_unassigned, $include_subcategories)
    {
        $category_id = (int) $category_id;
        if ($category_id <= 0) {
            return array();
        }

        $category_ids = array($category_id);
        if ($include_subcategories) {
            $category_model = new shopCategoryModel();
            $descendants = $category_model
                ->descendants($category_id, true)
                ->fetchAll('id');
            if ($descendants) {
                $category_ids = array_map('intval', array_keys($descendants));
            }
        }

        $where = 'category_id IN (i:ids) AND status >= 0';
        if ($only_unassigned) {
            $where .= ' AND (manager IS NULL OR manager = 0)';
        }

        $model = new shopProductModel();
        $sql = 'SELECT id FROM shop_product WHERE ' . $where;

        return array_map('intval', $model->query($sql, array(
            'ids' => $category_ids,
        ))->fetchAll(null, true));
    }

    protected function fetchGroupedCounts($sql)
    {
        $result = array();
        foreach ((new waModel())->query($sql) as $row) {
            $result[(int) $row['category_id']] = array(
                'total' => (int) $row['total'],
                'unassigned' => (int) $row['unassigned'],
            );
        }
        return $result;
    }
}
