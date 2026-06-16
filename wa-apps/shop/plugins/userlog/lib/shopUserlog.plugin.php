<?php

class shopUserlogPlugin extends shopPlugin
{
    /** @var int[] */
    protected static $logged_products = array();

    /** @var int[] */
    protected static $logged_categories = array();

    /** @var string[] */
    protected static $logged_sets = array();

    /** @var int[] */
    protected static $logged_orders = array();

    /** @var array<int, array> */
    protected static $seo_category_before = array();

    /** @var array<int, array> */
    protected static $seo_product_before = array();

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
     * Plugin class files may be missing from shop autoload cache until it is rebuilt.
     */
    protected function loadPluginClasses()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $dir = $this->path.'/lib/classes/';
        if (is_dir($dir)) {
            foreach (glob($dir.'*.class.php') as $file) {
                require_once $file;
            }
        }
        $loaded = true;
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
        $this->loadPluginClasses();
        $restore_app = wa()->getApp();
        if (!waSystem::isLoaded('userlog')) {
            wa('userlog');
        }
        if ($restore_app && $restore_app !== 'userlog') {
            wa($restore_app);
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
            if ($product_id && shopUserlogSeoSnapshot::isAvailable() && !isset(self::$seo_product_before[$product_id])) {
                $seo_state = shopUserlogSeoSnapshot::captureProductState($product_id);
                if ($seo_state) {
                    self::$seo_product_before[$product_id] = $seo_state;
                }
            }
            return;
        }
        $snapshot = shopUserlogProductSnapshot::captureForLog($product_id);
        if ($snapshot) {
            userlogLogger::rememberProductBefore($product_id, $snapshot);
        }
        if (shopUserlogSeoSnapshot::isAvailable() && !isset(self::$seo_product_before[$product_id])) {
            $seo_state = shopUserlogSeoSnapshot::captureProductState($product_id);
            if ($seo_state) {
                self::$seo_product_before[$product_id] = $seo_state;
            }
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
        $after_snapshot = shopUserlogProductSnapshot::captureForLog($product_id);
        $name = ifset($after_snapshot, 'product', 'name', '');
        $this->logSeoProductChange($product_id, $name);
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
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $category = is_array($params) ? $params : array();
        $category_id = (int) ifset($category, 'id', 0);
        if (!$category_id || !empty(self::$logged_categories[$category_id])) {
            return;
        }

        $before = userlogLogger::pullCategoryBefore($category_id);
        $after_snapshot = shopUserlogCategorySnapshot::captureForLog($category_id);
        $name = ifset($category, 'name', '#'.$category_id);
        if ($after_snapshot) {
            $name = ifset($after_snapshot, 'category', 'name', $name);
        }

        if ($after_snapshot) {
            if ($before) {
                $diff = userlogHelper::formatDiff(
                    shopUserlogCategorySnapshot::flattenForDiff($before),
                    shopUserlogCategorySnapshot::flattenForDiff($after_snapshot),
                    'category'
                );
                if ($diff) {
                    userlogLogger::log(array(
                        'app_id'       => 'shop',
                        'action'       => 'category.update',
                        'entity_type'  => 'category',
                        'entity_id'    => $category_id,
                        'entity_name'  => $name,
                        'summary'      => $this->buildCategoryUpdateSummary($name, $diff),
                        'before_data'  => $before,
                        'after_data'   => $after_snapshot,
                        'can_rollback' => $diff ? 1 : 0,
                    ));
                }
            } else {
                userlogLogger::log(array(
                    'app_id'       => 'shop',
                    'action'       => 'category.create',
                    'entity_type'  => 'category',
                    'entity_id'    => $category_id,
                    'entity_name'  => $name,
                    'summary'      => 'Создана категория «'.$name.'»',
                    'after_data'   => $after_snapshot,
                    'can_rollback' => 0,
                ));
            }
        }

        self::$logged_categories[$category_id] = true;
        $this->logSeoCategoryChange($category_id, $name);
    }

    public function prepareCategorySave($category_id)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $category_id = (int) $category_id;
        if (!$category_id) {
            return;
        }
        if (!userlogLogger::hasCategoryBefore($category_id)) {
            $snapshot = shopUserlogCategorySnapshot::captureForLog($category_id);
            if ($snapshot) {
                userlogLogger::rememberCategoryBefore($category_id, $snapshot);
            }
        }
        if (shopUserlogSeoSnapshot::isAvailable() && !isset(self::$seo_category_before[$category_id])) {
            $seo_state = shopUserlogSeoSnapshot::captureCategoryState($category_id);
            if ($seo_state) {
                self::$seo_category_before[$category_id] = $seo_state;
            }
        }
    }

    public function setSave($params)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $set = is_array($params) ? $params : array();
        $set_id = trim((string) ifset($set, 'id', ''));
        if ($set_id === '' || !empty(self::$logged_sets[$set_id])) {
            return;
        }

        $before = userlogLogger::pullSetBefore($set_id);
        $after_snapshot = $this->captureSetForLog($set_id);
        if (!$after_snapshot) {
            return;
        }

        $name = ifset($after_snapshot, 'set', 'name', $set_id);

        if ($before) {
            $diff = userlogHelper::formatDiff(
                userlogHelper::flattenSetForDiff($before),
                userlogHelper::flattenSetForDiff($after_snapshot),
                'set'
            );
            if (!$diff) {
                return;
            }
            userlogLogger::log(array(
                'app_id'       => 'shop',
                'action'       => 'set.update',
                'entity_type'  => 'set',
                'entity_id'    => 0,
                'entity_name'  => $name,
                'summary'      => $this->buildSetUpdateSummary($name, $diff),
                'before_data'  => $before,
                'after_data'   => $after_snapshot,
                'can_rollback' => 0,
            ));
        } else {
            userlogLogger::log(array(
                'app_id'       => 'shop',
                'action'       => 'set.create',
                'entity_type'  => 'set',
                'entity_id'    => 0,
                'entity_name'  => $name,
                'summary'      => 'Создан список «'.$name.'»',
                'after_data'   => $after_snapshot,
                'can_rollback' => 0,
            ));
        }

        self::$logged_sets[$set_id] = true;
    }

    public function prepareSetSave($set_id)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $set_id = trim((string) $set_id);
        if ($set_id === '' || userlogLogger::hasSetBefore($set_id)) {
            return;
        }
        $snapshot = $this->captureSetForLog($set_id);
        if ($snapshot) {
            userlogLogger::rememberSetBefore($set_id, $snapshot);
        }
    }

    protected function captureSetForLog($set_id)
    {
        $set = (new shopSetModel())->getById($set_id);
        if (!$set) {
            return null;
        }
        return array(
            'set'         => $set,
            'captured_at' => date('Y-m-d H:i:s'),
        );
    }

    /**
     * @param int $product_id
     * @param array|null $before_row shop_descriptionmanager row
     * @param array|null $after_row
     */
    public function logDescriptionmanagerChange($product_id, $before_row, $after_row)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = (int) $product_id;
        if (!$product_id) {
            return;
        }

        $product = (new shopProductModel())->getById($product_id);
        $name = ifset($product, 'name', '#'.$product_id);
        $before_desc = trim((string) ifset($before_row, 'description', ''));
        $after_desc = trim((string) ifset($after_row, 'description', ''));
        if ($before_desc === $after_desc) {
            return;
        }

        $diff = array(array(
            'field'  => 'manager_description',
            'label'  => 'Описание менеджера',
            'before' => userlogHelper::plainTextForDisplay($before_desc, 200),
            'after'  => userlogHelper::plainTextForDisplay($after_desc, 200),
        ));

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => $name,
            'summary'      => 'Изменено описание менеджера «'.$name.'» — '
                .$diff[0]['label'].': '.$diff[0]['before'].' → '.$diff[0]['after'],
            'before_data'  => array('descriptionmanager' => array('description' => $before_desc)),
            'after_data'   => array('descriptionmanager' => array('description' => $after_desc)),
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

        $diff = userlogHelper::formatCategoryTreeDiff($tree_before, $tree_after);
        $summary = 'Изменён порядок категорий в дереве';
        if ($diff) {
            $parts = array();
            foreach (array_slice($diff, 0, 5) as $line) {
                $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
            }
            $summary .= ' — '.implode('; ', $parts);
        }

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'category.sort',
            'entity_type'  => 'category',
            'entity_id'    => 0,
            'entity_name'  => '',
            'summary'      => $summary,
            'before_data'  => array('tree' => $tree_before),
            'after_data'   => array('tree' => $tree_after),
            'can_rollback' => $diff ? 1 : 0,
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

    protected function buildCategoryUpdateSummary($name, array $diff)
    {
        if (!$diff) {
            return 'Изменена категория «'.$name.'»';
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        return 'Изменена «'.$name.'» — '.implode('; ', $parts);
    }

    protected function buildSetUpdateSummary($name, array $diff)
    {
        if (!$diff) {
            return 'Изменён список «'.$name.'»';
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        return 'Изменён список «'.$name.'» — '.implode('; ', $parts);
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

    public function prepareOrderSave($order_id)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $order_id = (int) $order_id;
        if (!$order_id || userlogLogger::hasOrderBefore($order_id)) {
            return;
        }
        $snapshot = shopUserlogOrderSnapshot::captureForLog($order_id);
        if ($snapshot) {
            userlogLogger::rememberOrderBefore($order_id, $snapshot);
        }
    }

    public function finalizeOrderSave($order_id)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $order_id = (int) $order_id;
        if (!$order_id || !empty(self::$logged_orders[$order_id])) {
            return;
        }
        try {
            $this->logOrderEdit($order_id);
        } catch (Exception $e) {
            waLog::log('userlog finalizeOrderSave: '.$e->getMessage(), 'shop/userlog.log');
        }
    }

    protected function logOrderEdit($order_id)
    {
        $order_id = (int) $order_id;
        if (!$order_id || !empty(self::$logged_orders[$order_id])) {
            return;
        }

        $before = userlogLogger::pullOrderBefore($order_id);
        $after = shopUserlogOrderSnapshot::captureForLog($order_id);
        if (!$after) {
            return;
        }
        if (!$before) {
            return;
        }

        $diff = userlogHelper::formatDiff(
            shopUserlogOrderSnapshot::flattenForDiff($before),
            shopUserlogOrderSnapshot::flattenForDiff($after),
            'order'
        );
        if (!$diff) {
            return;
        }

        $label = $this->resolveOrderLabel($order_id);
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'order.update',
            'entity_type'  => 'order',
            'entity_id'    => $order_id,
            'entity_name'  => $label,
            'summary'      => $this->buildOrderUpdateSummary($label, $diff),
            'before_data'  => $before,
            'after_data'   => $after,
            'can_rollback' => $diff ? 1 : 0,
        ));
        self::$logged_orders[$order_id] = true;
    }

    protected function resolveOrderLabel($order_id)
    {
        $order_id = (int) $order_id;
        $label = '#'.$order_id;
        $order_row = (new shopOrderModel())->getById($order_id);
        if (!$order_row || empty($order_row['contact_id'])) {
            return $label;
        }
        $contact = new waContact($order_row['contact_id']);
        $name = trim($contact->getName());
        if ($name) {
            $label = $name.' (#'.$order_id.')';
        }
        return $label;
    }

    public function orderAction($data)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        try {
            $this->orderActionInternal($data);
        } catch (Exception $e) {
            waLog::log('userlog orderAction: '.$e->getMessage(), 'shop/userlog.log');
        }
    }

    protected function orderActionInternal($data)
    {
        if (!is_array($data)) {
            return;
        }

        $order_id = (int) ifset($data, 'order_id', 0);
        $action_id = (string) ifset($data, 'action_id', '');
        if (!$order_id || $action_id === '' || !empty(self::$logged_orders[$order_id])) {
            return;
        }

        $order_model = new shopOrderModel();
        $order_row = $order_model->getById($order_id);
        if (!$order_row) {
            return;
        }

        $label = '#'.$order_id;
        if (!empty($order_row['contact_id'])) {
            $contact = new waContact($order_row['contact_id']);
            $name = trim($contact->getName());
            if ($name) {
                $label = $name.' (#'.$order_id.')';
            }
        }

        if ($action_id === 'create') {
            $after = shopUserlogOrderSnapshot::captureForLog($order_id);
            userlogLogger::log(array(
                'app_id'       => 'shop',
                'action'       => 'order.create',
                'entity_type'  => 'order',
                'entity_id'    => $order_id,
                'entity_name'  => $label,
                'summary'      => 'Создан заказ '.$label,
                'after_data'   => $after,
                'can_rollback' => 0,
            ));
            self::$logged_orders[$order_id] = true;
            return;
        }

        if ($action_id === 'edit') {
            $this->logOrderEdit($order_id);
            return;
        }

        $before_state = ifset($data, 'before_state_id', '');
        $after_state = ifset($data, 'after_state_id', '');
        if ((string) $before_state === (string) $after_state) {
            return;
        }

        $workflow = new shopWorkflow();
        $before_state_obj = $before_state !== '' ? $workflow->getStateById($before_state) : null;
        $after_state_obj = $after_state !== '' ? $workflow->getStateById($after_state) : null;
        $before_name = $before_state_obj ? $before_state_obj->getName() : (string) $before_state;
        $after_name = $after_state_obj ? $after_state_obj->getName() : (string) $after_state;
        $action = $workflow->getActionById($action_id);
        $action_name = $action ? $action->getName() : $action_id;

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'order.update',
            'entity_type'  => 'order',
            'entity_id'    => $order_id,
            'entity_name'  => $label,
            'summary'      => 'Заказ '.$label.': «'.$before_name.'» → «'.$after_name.'» ('.$action_name.')',
            'before_data'  => array('state_id' => $before_state, 'state' => $before_name),
            'after_data'   => array('state_id' => $after_state, 'state' => $after_name, 'action_id' => $action_id),
            'can_rollback' => 1,
        ));
        self::$logged_orders[$order_id] = true;
    }

    public function logSettingsChange($section, array $before, array $after)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $diff = shopUserlogSettingsSnapshot::diff($before, $after);
        if (!$diff) {
            return;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'settings.update',
            'entity_type'  => 'settings',
            'entity_id'    => 0,
            'entity_name'  => $section,
            'summary'      => 'Настройки «'.$section.'» — '.implode('; ', $parts),
            'before_data'  => array('section' => $section, 'values' => $before),
            'after_data'   => array('section' => $section, 'values' => $after),
            'can_rollback' => 0,
        ));
    }

    public function logSliderChange(array $before_snapshot, array $after_snapshot)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $before = shopUserlogSliderSnapshot::flattenForDiff($before_snapshot);
        $after = shopUserlogSliderSnapshot::flattenForDiff($after_snapshot);
        $diff = shopUserlogSettingsSnapshot::diff($before, $after);
        if (!$diff) {
            return;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'settings.update',
            'entity_type'  => 'settings',
            'entity_id'    => 0,
            'entity_name'  => 'Слайдер',
            'summary'      => 'Слайдер — '.implode('; ', $parts),
            'before_data'  => $before_snapshot,
            'after_data'   => $after_snapshot,
            'can_rollback' => 0,
        ));
    }

    /**
     * @return array|null
     */
    public function captureSliderForLog()
    {
        if (!$this->ensureUserlogReady()) {
            return null;
        }
        return shopUserlogSliderSnapshot::captureAll();
    }

    public function productDuplicate($params)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $source = ifset($params, 'product');
        $duplicate = ifset($params, 'duplicate');
        if (!$source instanceof shopProduct || !$duplicate instanceof shopProduct) {
            return;
        }
        $source_id = (int) $source->getId();
        $new_id = (int) $duplicate->getId();
        if (!$source_id || !$new_id) {
            return;
        }

        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.create',
            'entity_type'  => 'product',
            'entity_id'    => $new_id,
            'entity_name'  => $duplicate->name,
            'summary'      => 'Дублирован товар «'.$source->name.'» → «'.$duplicate->name.'» (#'.$new_id.')',
            'before_data'  => array('source_product_id' => $source_id, 'source_name' => $source->name),
            'after_data'   => shopUserlogProductSnapshot::captureForLog($new_id),
            'can_rollback' => 0,
        ));
    }

    public function setDelete($set)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        if (!is_array($set)) {
            return;
        }
        $set_id = trim((string) ifset($set, 'id', ''));
        $name = ifset($set, 'name', $set_id);
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'set.delete',
            'entity_type'  => 'set',
            'entity_id'    => 0,
            'entity_name'  => $name,
            'summary'      => 'Удалён список «'.$name.'»',
            'before_data'  => array('set' => $set),
            'can_rollback' => 0,
        ));
    }

    public function logProductImageUpload($product_id, array $image)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = (int) $product_id;
        if (!$product_id) {
            return;
        }
        $product = (new shopProductModel())->getById($product_id);
        $name = ifset($product, 'name', '#'.$product_id);
        $filename = ifset($image, 'original_filename', ifset($image, 'filename', ''));
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => $name,
            'summary'      => 'Загружено изображение «'.$filename.'» для «'.$name.'»',
            'after_data'   => array('image' => $image),
            'can_rollback' => 0,
        ));
    }

    public function logProductImageDelete(array $image)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = (int) ifset($image, 'product_id', 0);
        if (!$product_id) {
            return;
        }
        $product = (new shopProductModel())->getById($product_id);
        $name = ifset($product, 'name', '#'.$product_id);
        $filename = ifset($image, 'original_filename', ifset($image, 'filename', ''));
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => $name,
            'summary'      => 'Удалено изображение «'.$filename.'» у «'.$name.'»',
            'before_data'  => array('image' => $image),
            'can_rollback' => 0,
        ));
    }

    public function logProductPageSave($page_id, $is_new = false)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $page_id = (int) $page_id;
        if (!$page_id) {
            return;
        }
        $after_snapshot = shopUserlogProductPageSnapshot::captureForLog($page_id);
        if (!$after_snapshot) {
            return;
        }
        $page = ifset($after_snapshot, 'page', array());
        $product_id = (int) ifset($page, 'product_id', 0);
        $product = $product_id ? (new shopProductModel())->getById($product_id) : null;
        $product_name = ifset($product, 'name', '#'.$product_id);
        $page_name = ifset($page, 'name', '');

        if ($is_new) {
            userlogLogger::log(array(
                'app_id'       => 'shop',
                'action'       => 'product.update',
                'entity_type'  => 'product',
                'entity_id'    => $product_id,
                'entity_name'  => $product_name,
                'summary'      => 'Создана подстраница «'.$page_name.'» у «'.$product_name.'»',
                'after_data'   => $after_snapshot,
                'can_rollback' => 0,
            ));
            return;
        }

        $before = userlogLogger::pullPageBefore($page_id);
        if (!$before) {
            return;
        }
        $diff = shopUserlogSettingsSnapshot::diff(
            shopUserlogProductPageSnapshot::flattenForDiff($before),
            shopUserlogProductPageSnapshot::flattenForDiff($after_snapshot)
        );
        if (!$diff) {
            return;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => $product_name,
            'summary'      => 'Подстраница «'.$page_name.'» («'.$product_name.'») — '.implode('; ', $parts),
            'before_data'  => $before,
            'after_data'   => $after_snapshot,
            'can_rollback' => 0,
        ));
    }

    public function prepareProductPageSave($page_id)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $page_id = (int) $page_id;
        if (!$page_id || userlogLogger::hasPageBefore($page_id)) {
            return;
        }
        $snapshot = shopUserlogProductPageSnapshot::captureForLog($page_id);
        if ($snapshot) {
            userlogLogger::rememberPageBefore($page_id, $snapshot);
        }
    }

    public function logProductPageDelete(array $page)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = (int) ifset($page, 'product_id', 0);
        $page_name = ifset($page, 'name', '');
        $product = $product_id ? (new shopProductModel())->getById($product_id) : null;
        $product_name = ifset($product, 'name', '#'.$product_id);
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => $product_name,
            'summary'      => 'Удалена подстраница «'.$page_name.'» у «'.$product_name.'»',
            'before_data'  => array('page' => $page),
            'can_rollback' => 0,
        ));
    }

    public function logProductPageMove(array $page, $sort_before, $sort_after)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        if ((int) $sort_before === (int) $sort_after) {
            return;
        }
        $product_id = (int) ifset($page, 'product_id', 0);
        $page_name = ifset($page, 'name', '');
        $product = $product_id ? (new shopProductModel())->getById($product_id) : null;
        $product_name = ifset($product, 'name', '#'.$product_id);
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => $product_name,
            'summary'      => 'Подстраница «'.$page_name.'» («'.$product_name.'»): порядок '
                .$sort_before.' → '.$sort_after,
            'before_data'  => array('page' => $page, 'sort' => $sort_before),
            'after_data'   => array('sort' => $sort_after),
            'can_rollback' => 0,
        ));
    }

    public function logProductServiceChange($product_id, $service_id, array $before_snapshot)
    {
        if (self::isLoggingSuspended() || !$this->ensureUserlogReady()) {
            return;
        }
        $product_id = (int) $product_id;
        $service_id = (int) $service_id;
        if (!$product_id || !$service_id) {
            return;
        }
        $after_snapshot = shopUserlogProductServiceSnapshot::captureForLog($product_id, $service_id);
        if (!$after_snapshot) {
            return;
        }
        $diff = shopUserlogSettingsSnapshot::diff(
            shopUserlogProductServiceSnapshot::flattenForDiff($before_snapshot),
            shopUserlogProductServiceSnapshot::flattenForDiff($after_snapshot)
        );
        if (!$diff) {
            return;
        }
        $product = (new shopProductModel())->getById($product_id);
        $product_name = ifset($product, 'name', '#'.$product_id);
        $service_name = ifset($after_snapshot, 'service_name', '#'.$service_id);
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => $product_id,
            'entity_name'  => $product_name,
            'summary'      => 'Услуга «'.$service_name.'» у «'.$product_name.'» — '.implode('; ', $parts),
            'before_data'  => $before_snapshot,
            'after_data'   => $after_snapshot,
            'can_rollback' => 0,
        ));
    }

    protected function logSeoCategoryChange($category_id, $name)
    {
        $state_json = waRequest::request('seo_state');
        if (!$state_json || !shopUserlogSeoSnapshot::isAvailable()) {
            unset(self::$seo_category_before[$category_id]);
            return;
        }
        $before_state = ifset(self::$seo_category_before, $category_id);
        unset(self::$seo_category_before[$category_id]);
        $before_flat = shopUserlogSeoSnapshot::flattenEntityState($before_state);
        $after_flat = shopUserlogSeoSnapshot::flattenFromRequestJson($state_json);
        $diff = shopUserlogSettingsSnapshot::diff($before_flat, $after_flat);
        if (!$diff) {
            return;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'category.update',
            'entity_type'  => 'category',
            'entity_id'    => (int) $category_id,
            'entity_name'  => $name,
            'summary'      => 'SEO категории «'.$name.'» — '.implode('; ', $parts),
            'before_data'  => array('seo' => $before_state),
            'after_data'   => array('seo' => json_decode($state_json, true)),
            'can_rollback' => 0,
        ));
    }

    protected function logSeoProductChange($product_id, $name)
    {
        $state_json = waRequest::request('seo_state');
        if (!$state_json || !shopUserlogSeoSnapshot::isAvailable()) {
            unset(self::$seo_product_before[$product_id]);
            return;
        }
        $before_state = ifset(self::$seo_product_before, $product_id);
        unset(self::$seo_product_before[$product_id]);
        $before_flat = shopUserlogSeoSnapshot::flattenEntityState($before_state);
        $after_flat = shopUserlogSeoSnapshot::flattenFromRequestJson($state_json);
        $diff = shopUserlogSettingsSnapshot::diff($before_flat, $after_flat);
        if (!$diff) {
            return;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'product.update',
            'entity_type'  => 'product',
            'entity_id'    => (int) $product_id,
            'entity_name'  => $name,
            'summary'      => 'SEO товара «'.$name.'» — '.implode('; ', $parts),
            'before_data'  => array('seo' => $before_state),
            'after_data'   => array('seo' => json_decode($state_json, true)),
            'can_rollback' => 0,
        ));
    }

    protected function buildOrderUpdateSummary($label, array $diff)
    {
        if (!$diff) {
            return 'Изменён заказ '.$label;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        return 'Изменён заказ '.$label.' — '.implode('; ', $parts);
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
