<?php

class shopUserlogPlugin extends shopPlugin
{
    /** @var int[] */
    protected static $logged_products = array();

    /** @var bool */
    protected static $logging_suspended = false;

    public static function setLoggingSuspended($flag)
    {
        self::$logging_suspended = (bool) $flag;
    }

    public static function isLoggingSuspended()
    {
        return self::$logging_suspended;
    }

    /**
     * Load userlog app before using its classes (autoload is app-scoped).
     *
     * @return bool
     */
    protected function ensureUserlogReady()
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        if (!wa()->appExists('userlog')) {
            return $ready = false;
        }
        if (!waSystem::isLoaded('userlog')) {
            wa('userlog');
        }
        return $ready = class_exists('userlogHelper');
    }

    public function prepareProductSave($product_id)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = (int) $product_id;
        if (!$product_id || userlogLogger::hasProductBefore($product_id)) {
            return;
        }
        $snapshot = shopUserlogProductSnapshot::captureForLog($product_id);
        if ($snapshot) {
            userlogLogger::rememberProductBefore($product_id, $snapshot);
        }
    }

    public function productPresave($params)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = $this->resolveProductId($params);
        if ($product_id) {
            $this->prepareProductSave($product_id);
        }
    }

    public function productSave($params)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = $this->resolveProductId($params);
        if (!$product_id) {
            return;
        }
        $this->finalizeProductSave($product_id, ifset($params, 'data', array()));
    }

    public function productDelete($params)
    {
    }

    /**
     * @param int $product_id
     * @param array $data
     */
    public function finalizeProductSave($product_id, array $data = array())
    {
        $product_id = (int) $product_id;
        if (!$product_id || !empty(self::$logged_products[$product_id])) {
            return;
        }

        $before = userlogLogger::pullProductBefore($product_id);
        $after_snapshot = shopUserlogProductSnapshot::captureForLog($product_id);
        if (!$after_snapshot) {
            return;
        }

        $after = ifset($after_snapshot, 'product', array());
        $existed_before = (bool) $before;

        if ($existed_before) {
            $diff = userlogHelper::formatDiff(
                shopUserlogProductSnapshot::flattenForDiff($before),
                shopUserlogProductSnapshot::flattenForDiff($after_snapshot),
                'product'
            );
            if (!$diff) {
                return;
            }
            if ($this->hasRecentDuplicateEvent($product_id, $diff)) {
                return;
            }
            userlogLogger::log(array(
                'app_id'       => 'shop',
                'action'       => 'product.update',
                'entity_type'  => 'product',
                'entity_id'    => $product_id,
                'entity_name'  => ifset($after, 'name', ''),
                'summary'      => $this->buildProductUpdateSummary($after, $diff),
                'before_data'  => $before,
                'after_data'   => $after_snapshot,
                'can_rollback' => $diff ? 1 : 0,
            ));
        } else {
            userlogLogger::log(array(
                'app_id'       => 'shop',
                'action'       => 'product.create',
                'entity_type'  => 'product',
                'entity_id'    => $product_id,
                'entity_name'  => ifset($after, 'name', ifset($data, 'name', '')),
                'summary'      => 'Создан товар «'.ifset($after, 'name', ifset($data, 'name', '#'.$product_id)).'»',
                'after_data'   => $after_snapshot,
                'can_rollback' => 0,
            ));
        }

        self::$logged_products[$product_id] = true;
    }

    /**
     * @param int $product_id
     * @param array $before_skus sku_id => row
     * @param array $after_snapshot from captureForLog
     */
    public function logEditpriceChange($product_id, array $before_skus, array $after_snapshot)
    {
        if (!$this->ensureUserlogReady()) {
            return;
        }
        $product_id = (int) $product_id;
        if (!$product_id || !empty(self::$logged_products[$product_id])) {
            return;
        }

        $before = array(
            'product'    => ifset($after_snapshot, 'product', array()),
            'skus'       => $before_skus,
            'categories' => ifset($after_snapshot, 'categories', array()),
        );
        $after = ifset($after_snapshot, 'product', array());
        $diff = userlogHelper::formatDiff(
            shopUserlogProductSnapshot::flattenForDiff($before),
            shopUserlogProductSnapshot::flattenForDiff($after_snapshot),
            'product'
        );

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => ifset($after, 'name', ''),
            'summary'      => $this->buildProductUpdateSummary($after, $diff, 'Массовое изменение цен'),
            'before_data'  => $before,
            'after_data'   => $after_snapshot,
            'can_rollback' => $diff ? 1 : 0,
        ));
        self::$logged_products[$product_id] = true;
    }

    public function categorySave($params)
    {
        if (!$this->ensureUserlogReady()) {
            return;
        }
        $category = is_array($params) ? $params : array();
        $category_id = (int) ifset($category, 'id', 0);
        if (!$category_id) {
            return;
        }

        $is_new = !empty($category['__new']);
        $action = $is_new ? 'category.create' : 'category.update';
        $verb = $is_new ? 'Создана' : 'Изменена';

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => $action,
            'entity_type'  => 'category',
            'entity_id'    => $category_id,
            'entity_name'  => ifset($category, 'name', ''),
            'summary'      => $verb.' категория «'.ifset($category, 'name', '#'.$category_id).'»',
            'after_data'   => $category,
            'can_rollback' => 0,
        ));
    }

    public function categoryDelete($params)
    {
    }

    public function beforeProductsDelete(array $product_ids)
    {
        if (!$this->ensureUserlogReady() || !$product_ids) {
            return;
        }
        (new userlogTrashService())->trashProducts($product_ids);
    }

    public function beforeCategoryDelete($category_id)
    {
        if (!$this->ensureUserlogReady()) {
            return;
        }
        (new userlogTrashService())->trashCategory($category_id);
    }

    public function logProductSort($category_id, array $product_ids, $before_id, array $sort_before)
    {
        if (!$this->ensureUserlogReady()) {
            return;
        }

        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        $cp_model = new shopCategoryProductsModel();
        $sort_after = array();
        $product_model = new shopProductModel();
        $names = $product_model->select('id, name')->where('id IN (i:ids)', array('ids' => $product_ids))->fetchAll('id');

        foreach ($product_ids as $pid) {
            $row = $cp_model->getByField(array('category_id' => $category_id, 'product_id' => $pid));
            if ($row) {
                $sort_after[$pid] = (int) $row['sort'];
            }
        }

        $diff = userlogHelper::formatSortDiff($sort_before, $sort_after, $names);
        $summary = 'Сортировка в «'.ifset($category, 'name', 'категории').'»';
        if ($diff) {
            $parts = array();
            foreach (array_slice($diff, 0, 3) as $line) {
                $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
            }
            $summary .= ' — '.implode('; ', $parts);
        }

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.sort',
            'entity_type'  => 'category',
            'entity_id'    => $category_id,
            'entity_name'  => ifset($category, 'name', ''),
            'summary'      => $summary,
            'before_data'  => array(
                'category_id' => $category_id,
                'items'       => $sort_before,
                'before_id'   => $before_id,
                'product_ids' => $product_ids,
            ),
            'after_data'   => array('items' => $sort_after, 'names' => $names),
            'can_rollback' => 1,
        ));
    }

    public function logCategorySort(array $tree_before, array $tree_after)
    {
        if (!$this->ensureUserlogReady()) {
            return;
        }
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'category.sort',
            'entity_type'  => 'category',
            'entity_id'    => 0,
            'entity_name'  => '',
            'summary'      => 'Изменён порядок категорий в дереве',
            'before_data'  => array('tree' => $tree_before),
            'after_data'   => array('tree' => $tree_after),
            'can_rollback' => 0,
        ));
    }

    /**
     * @param array $row shop_category row
     * @return array
     */
    public static function snapshotCategoryMove(array $row)
    {
        $parent_id = (int) ifset($row, 'parent_id', 0);
        $parent_name = '';
        if ($parent_id) {
            $parent = (new shopCategoryModel())->getById($parent_id);
            $parent_name = ifset($parent, 'name', '');
        }
        return array(
            'id'          => (int) ifset($row, 'id', 0),
            'name'        => ifset($row, 'name', ''),
            'parent_id'   => $parent_id,
            'parent_name' => $parent_name,
            'sort'        => (int) ifset($row, 'sort', 0),
        );
    }

    public function logCategoryMove($category_id, array $before, array $after)
    {
        if (!$this->ensureUserlogReady()) {
            return;
        }
        $category_id = (int) $category_id;
        if (!$category_id) {
            return;
        }

        $parent_changed = (int) ifset($before, 'parent_id', 0) !== (int) ifset($after, 'parent_id', 0);
        $sort_changed = (int) ifset($before, 'sort', 0) !== (int) ifset($after, 'sort', 0);
        if (!$parent_changed && !$sort_changed) {
            return;
        }

        $name = ifset($after, 'name', ifset($before, 'name', '#'.$category_id));
        if ($parent_changed) {
            $summary = 'Категория «'.$name.'» перемещена: '
                .$this->formatCategoryParentLabel($before)
                .' → '
                .$this->formatCategoryParentLabel($after);
            $action = 'category.move';
        } else {
            $summary = 'Категория «'.$name.'»: порядок '
                .ifset($before, 'sort', 0).' → '.ifset($after, 'sort', 0);
            $action = 'category.sort';
        }

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => $action,
            'entity_type'  => 'category',
            'entity_id'    => $category_id,
            'entity_name'  => $name,
            'summary'      => $summary,
            'before_data'  => $before,
            'after_data'   => $after,
            'can_rollback' => 1,
        ));
    }

    protected function formatCategoryParentLabel(array $snapshot)
    {
        $parent_name = trim((string) ifset($snapshot, 'parent_name', ''));
        if ($parent_name !== '') {
            return '«'.$parent_name.'»';
        }
        return (int) ifset($snapshot, 'parent_id', 0) ? '#'.(int) $snapshot['parent_id'] : 'Корень';
    }

    protected function resolveProductId($params)
    {
        $data = ifset($params, 'data', array());
        $product_id = (int) ifset($data, 'id', 0);
        if (!$product_id) {
            $instance = ifset($params, 'instance');
            if ($instance instanceof shopProduct) {
                $product_id = (int) $instance->getId();
            }
        }
        if (!$product_id && isset($params['id'])) {
            $product_id = (int) $params['id'];
        }
        return $product_id;
    }

    protected function buildProductUpdateSummary(array $product, array $diff, $prefix = null)
    {
        $name = ifset($product, 'name', 'товар');
        if (!$diff) {
            return ($prefix ? $prefix.': ' : '').'Изменён товар «'.$name.'»';
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        $head = ($prefix ? $prefix.': ' : '').'Изменён «'.$name.'» — ';
        return $head.implode('; ', $parts);
    }

    /**
     * Skip duplicate log lines from repeated save/product_save without real changes.
     */
    protected function hasRecentDuplicateEvent($product_id, array $diff)
    {
        if (!$this->ensureUserlogReady() || !$diff) {
            return false;
        }
        $model = new userlogEventModel();
        $recent = $model->query(
            "SELECT summary FROM userlog_event
             WHERE entity_id = i:id AND action = 'product.update'
               AND datetime >= s:since
             ORDER BY id DESC LIMIT 3",
            array(
                'id'    => (int) $product_id,
                'since' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            )
        )->fetchAll(null, true);
        if (!$recent) {
            return false;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        $needle = implode('; ', $parts);
        foreach ($recent as $summary) {
            if ($summary && strpos($summary, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return shopUserlogPlugin|null
     */
    public static function getInstance()
    {
        if (!wa()->appExists('shop')) {
            return null;
        }
        $plugin = wa('shop')->getPlugin('userlog');
        return $plugin instanceof shopUserlogPlugin ? $plugin : null;
    }
}
