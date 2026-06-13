<?php

class userlogLogger
{
    protected static $product_before = array();
    protected static $post_before = array();
    protected static $category_before = array();
    protected static $set_before = array();
    protected static $order_before = array();
    protected static $page_before = array();

    public static function log(array $data)
    {
        if (!userlogHelper::ensureAppLoaded()) {
            return false;
        }
        try {
            return (new userlogEventModel())->log($data);
        } catch (Exception $e) {
            waLog::log('userlog: '.$e->getMessage(), 'userlog/error.log');
            return false;
        }
    }

    public static function rememberProductBefore($product_id, array $data)
    {
        self::$product_before[(int) $product_id] = $data;
    }

    public static function hasProductBefore($product_id)
    {
        return isset(self::$product_before[(int) $product_id]);
    }

    public static function pullProductBefore($product_id)
    {
        $product_id = (int) $product_id;
        if (!isset(self::$product_before[$product_id])) {
            return null;
        }
        $data = self::$product_before[$product_id];
        unset(self::$product_before[$product_id]);
        return $data;
    }

    public static function rememberPostBefore($post_id, array $data)
    {
        self::$post_before[(int) $post_id] = $data;
    }

    public static function hasPostBefore($post_id)
    {
        return isset(self::$post_before[(int) $post_id]);
    }

    public static function pullPostBefore($post_id)
    {
        $post_id = (int) $post_id;
        if (!isset(self::$post_before[$post_id])) {
            return null;
        }
        $data = self::$post_before[$post_id];
        unset(self::$post_before[$post_id]);
        return $data;
    }

    public static function rememberCategoryBefore($category_id, array $data)
    {
        self::$category_before[(int) $category_id] = $data;
    }

    public static function hasCategoryBefore($category_id)
    {
        return isset(self::$category_before[(int) $category_id]);
    }

    public static function pullCategoryBefore($category_id)
    {
        $category_id = (int) $category_id;
        if (!isset(self::$category_before[$category_id])) {
            return null;
        }
        $data = self::$category_before[$category_id];
        unset(self::$category_before[$category_id]);
        return $data;
    }

    public static function rememberSetBefore($set_id, array $data)
    {
        self::$set_before[(string) $set_id] = $data;
    }

    public static function hasSetBefore($set_id)
    {
        return isset(self::$set_before[(string) $set_id]);
    }

    public static function pullSetBefore($set_id)
    {
        $set_id = (string) $set_id;
        if (!isset(self::$set_before[$set_id])) {
            return null;
        }
        $data = self::$set_before[$set_id];
        unset(self::$set_before[$set_id]);
        return $data;
    }

    public static function rememberOrderBefore($order_id, array $data)
    {
        self::$order_before[(int) $order_id] = $data;
    }

    public static function hasOrderBefore($order_id)
    {
        return isset(self::$order_before[(int) $order_id]);
    }

    public static function pullOrderBefore($order_id)
    {
        $order_id = (int) $order_id;
        if (!isset(self::$order_before[$order_id])) {
            return null;
        }
        $data = self::$order_before[$order_id];
        unset(self::$order_before[$order_id]);
        return $data;
    }

    public static function rememberPageBefore($page_id, array $data)
    {
        self::$page_before[(int) $page_id] = $data;
    }

    public static function hasPageBefore($page_id)
    {
        return isset(self::$page_before[(int) $page_id]);
    }

    public static function pullPageBefore($page_id)
    {
        $page_id = (int) $page_id;
        if (!isset(self::$page_before[$page_id])) {
            return null;
        }
        $data = self::$page_before[$page_id];
        unset(self::$page_before[$page_id]);
        return $data;
    }

    public static function syncFromWaLog($full_backfill = false)
    {
        self::syncAuthFromWaLog();
        self::syncShopFromWaLog($full_backfill);
        self::syncBlogFromWaLog($full_backfill);
    }

    /**
     * Fast sync for backend UI: auth tail + recent blog actions (no multi-year backfill).
     */
    public static function syncForDisplay()
    {
        self::syncAuthFromWaLog();
        self::syncRecentBlogFromWaLog(30);
    }

    public static function syncRecentShopFromWaLog($days = 30)
    {
        if (!userlogHelper::ensureAppLoaded()) {
            return;
        }
        if (!wa()->appExists('shop')) {
            return;
        }
        wa('shop');

        $days = max(1, min(365, (int) $days));
        $since = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));

        $actions = array(
            'product_edit'    => 'product.update',
            'product_add'     => 'product.create',
            'product_delete'  => 'product.delete',
            'category_add'    => 'category.create',
            'category_edit'   => 'category.update',
            'category_delete' => 'category.delete',
        );

        $sql = "SELECT * FROM wa_log
                WHERE app_id = 'shop'
                  AND action IN (s:actions)
                  AND datetime >= s:since
                ORDER BY id DESC
                LIMIT 50";

        $rows = (new waModel())->query($sql, array(
            'actions' => array_keys($actions),
            'since'   => $since,
        ))->fetchAll();
        if (!$rows) {
            return;
        }

        self::importShopWaLogRows($rows, $actions, false);

        $asm = new waAppSettingsModel();
        $cursor = (int) $asm->get('userlog', 'wa_log_shop_last_id', 0);
        foreach ($rows as $row) {
            $cursor = max($cursor, (int) $row['id']);
        }
        $asm->set('userlog', 'wa_log_shop_last_id', $cursor);
    }

    public static function syncAuthFromWaLog()
    {
        if (!userlogHelper::ensureAppLoaded()) {
            return;
        }
        $asm = new waAppSettingsModel();
        $last_id = (int) $asm->get('userlog', 'wa_log_auth_last_id', 0);

        $sql = "SELECT * FROM wa_log
                WHERE id > i:last_id
                  AND action IN ('login', 'logout', 'login_failed')
                ORDER BY id ASC
                LIMIT 500";
        $rows = (new waModel())->query($sql, array('last_id' => $last_id))->fetchAll();
        if (!$rows) {
            return;
        }

        $event_model = new userlogEventModel();
        $map = array(
            'login'        => 'auth.login',
            'logout'       => 'auth.logout',
            'login_failed' => 'auth.login_failed',
        );

        foreach ($rows as $row) {
            if ($event_model->existsByWaLogId((int) $row['id'])) {
                $last_id = max($last_id, (int) $row['id']);
                continue;
            }
            $action = ifset($map, $row['action'], 'auth.'.$row['action']);
            $params = self::parseWaLogParams($row['params']);
            $summary = self::authSummary($action, $params, $row);
            $event_model->log(array(
                'contact_id'   => (int) $row['contact_id'],
                'app_id'       => $row['app_id'],
                'action'       => $action,
                'entity_type'  => 'auth',
                'summary'      => $summary,
                'after_data'   => self::withWaLogId(is_array($params) ? $params : array('raw' => $params), $row['id']),
                'datetime'     => $row['datetime'],
                'can_rollback' => 0,
                'ip'           => is_array($params) ? ifset($params, 'ip', null) : null,
            ));
            $last_id = max($last_id, (int) $row['id']);
        }

        $asm->set('userlog', 'wa_log_auth_last_id', $last_id);
        // migrate legacy cursor
        $legacy = (int) $asm->get('userlog', 'wa_log_last_id', 0);
        if ($legacy > $last_id) {
            $asm->set('userlog', 'wa_log_auth_last_id', $legacy);
        }
    }

    public static function syncShopFromWaLog($full_backfill = false)
    {
        if (!userlogHelper::ensureAppLoaded()) {
            return;
        }
        if (!wa()->appExists('shop')) {
            return;
        }
        wa('shop');

        $asm = new waAppSettingsModel();
        $setting_key = 'wa_log_shop_last_id';
        $last_id = $full_backfill ? 0 : (int) $asm->get('userlog', $setting_key, 0);

        $actions = array(
            'product_edit'   => 'product.update',
            'product_add'    => 'product.create',
            'product_delete' => 'product.delete',
            'category_add'   => 'category.create',
            'category_edit'  => 'category.update',
            'category_delete'=> 'category.delete',
        );

        $sql = "SELECT * FROM wa_log
                WHERE id > i:last_id
                  AND app_id = 'shop'
                  AND action IN (s:actions)
                ORDER BY id ASC
                LIMIT 500";
        do {
            $rows = (new waModel())->query($sql, array(
                'last_id' => $last_id,
                'actions' => array_keys($actions),
            ))->fetchAll();
            if (!$rows) {
                break;
            }

            self::importShopWaLogRows($rows, $actions, false, $last_id);
        } while ($full_backfill && count($rows) >= 500);

        if (!$full_backfill || $last_id) {
            $asm->set('userlog', $setting_key, $last_id);
        }
    }

    public static function syncRecentBlogFromWaLog($days = 30)
    {
        if (!userlogHelper::ensureAppLoaded()) {
            return;
        }
        if (!wa()->appExists('blog')) {
            return;
        }
        wa('blog');

        $days = max(1, min(365, (int) $days));
        $since = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));

        $actions = self::blogWaLogActionMap();

        $sql = "SELECT * FROM wa_log
                WHERE app_id = 'blog'
                  AND action IN (s:actions)
                  AND datetime >= s:since
                ORDER BY id DESC
                LIMIT 50";

        $rows = (new waModel())->query($sql, array(
            'actions' => array_keys($actions),
            'since'   => $since,
        ))->fetchAll();
        if (!$rows) {
            return;
        }

        self::importBlogWaLogRows($rows, $actions);

        $asm = new waAppSettingsModel();
        $cursor = (int) $asm->get('userlog', 'wa_log_blog_last_id', 0);
        foreach ($rows as $row) {
            $cursor = max($cursor, (int) $row['id']);
        }
        $asm->set('userlog', 'wa_log_blog_last_id', $cursor);
    }

    public static function syncBlogFromWaLog($full_backfill = false)
    {
        if (!userlogHelper::ensureAppLoaded()) {
            return;
        }
        if (!wa()->appExists('blog')) {
            return;
        }
        wa('blog');

        $asm = new waAppSettingsModel();
        $setting_key = 'wa_log_blog_last_id';
        $last_id = $full_backfill ? 0 : (int) $asm->get('userlog', $setting_key, 0);

        $actions = self::blogWaLogActionMap();

        $sql = "SELECT * FROM wa_log
                WHERE id > i:last_id
                  AND app_id = 'blog'
                  AND action IN (s:actions)
                ORDER BY id ASC
                LIMIT 500";
        do {
            $rows = (new waModel())->query($sql, array(
                'last_id' => $last_id,
                'actions' => array_keys($actions),
            ))->fetchAll();
            if (!$rows) {
                break;
            }

            self::importBlogWaLogRows($rows, $actions, $last_id);
        } while ($full_backfill && count($rows) >= 500);

        if (!$full_backfill || $last_id) {
            $asm->set('userlog', $setting_key, $last_id);
        }
    }

    protected static function blogWaLogActionMap()
    {
        return array(
            'post_edit'      => 'post.update',
            'post_add'       => 'post.create',
            'post_delete'    => 'post.delete',
            'post_publish'   => 'post.update',
            'post_unpublish' => 'post.update',
        );
    }

    protected static function importBlogWaLogRows(array $rows, array $actions, &$last_id = null)
    {
        $event_model = new userlogEventModel();
        $post_model = new blogPostModel();

        foreach ($rows as $row) {
            if ($event_model->existsByWaLogId((int) $row['id'])) {
                if ($last_id !== null) {
                    $last_id = max($last_id, (int) $row['id']);
                }
                continue;
            }

            if (self::isBlogUserlogPluginActive() && in_array($row['action'], array('post_edit', 'post_publish', 'post_unpublish'), true)) {
                if ($last_id !== null) {
                    $last_id = max($last_id, (int) $row['id']);
                }
                continue;
            }

            $entity_id = self::resolveBlogEntityId($row['params']);
            $action = $actions[$row['action']];
            if ($entity_id && $event_model->findRicherDuplicate(array(
                'id'         => 0,
                'entity_id'  => $entity_id,
                'action'     => $action,
                'datetime'   => $row['datetime'],
            ))) {
                if ($last_id !== null) {
                    $last_id = max($last_id, (int) $row['id']);
                }
                continue;
            }

            $entity_name = '';
            $after_data = self::withWaLogId(array('source' => 'wa_log'), $row['id']);
            if ($entity_id) {
                $post = $post_model->getById($entity_id);
                if ($post) {
                    $entity_name = $post['title'];
                }
            }

            $summary = self::blogSummary($row['action'], $entity_id, $entity_name);

            $event_model->log(array(
                'contact_id'   => (int) $row['contact_id'],
                'app_id'       => 'blog',
                'action'       => $action,
                'entity_type'  => 'post',
                'entity_id'    => $entity_id ?: null,
                'entity_name'  => $entity_name,
                'summary'      => $summary,
                'after_data'   => $after_data,
                'datetime'     => $row['datetime'],
                'can_rollback' => 0,
            ));
            if ($last_id !== null) {
                $last_id = max($last_id, (int) $row['id']);
            }
        }
    }

    protected static function resolveBlogEntityId($params)
    {
        if (is_numeric($params)) {
            return (int) $params;
        }
        if (is_string($params) && preg_match('/^\d+(,\d+)*$/', trim($params))) {
            return (int) strtok($params, ',');
        }
        if (is_array($params) && isset($params['id'])) {
            return (int) $params['id'];
        }
        return 0;
    }

    protected static function blogSummary($wa_action, $entity_id, $entity_name)
    {
        $name_part = $entity_name ? ' «'.$entity_name.'»' : ($entity_id ? ' #'.$entity_id : '');
        $map = array(
            'post_edit'      => 'Изменена запись'.$name_part,
            'post_add'       => 'Создана запись'.$name_part,
            'post_delete'    => 'Удалена запись'.$name_part,
            'post_publish'   => 'Опубликована запись'.$name_part,
            'post_unpublish' => 'Снята с публикации запись'.$name_part,
        );
        return ifset($map, $wa_action, $wa_action.$name_part);
    }

    protected static function isBlogUserlogPluginActive()
    {
        if (!wa()->appExists('blog')) {
            return false;
        }
        wa('blog');
        $plugins = wa('blog')->getConfig()->getPlugins();
        return isset($plugins['userlog']);
    }

    protected static function importShopWaLogRows(array $rows, array $actions, $with_snapshots = true, &$last_id = null)
    {
        $event_model = new userlogEventModel();
        $product_model = new shopProductModel();
        $category_model = new shopCategoryModel();

        foreach ($rows as $row) {
            if ($event_model->existsByWaLogId((int) $row['id'])) {
                if ($last_id !== null) {
                    $last_id = max($last_id, (int) $row['id']);
                }
                continue;
            }

            $entity_id = self::resolveShopEntityId($row['params']);
            $action = $actions[$row['action']];
            if ($entity_id && $event_model->findRicherDuplicate(array(
                'id'         => 0,
                'entity_id'  => $entity_id,
                'action'     => $action,
                'datetime'   => $row['datetime'],
            ))) {
                if ($last_id !== null) {
                    $last_id = max($last_id, (int) $row['id']);
                }
                continue;
            }

            $entity_type = strpos($action, 'product.') === 0 ? 'product' : 'category';
            $entity_name = '';
            $after_data = self::withWaLogId(array('source' => 'wa_log'), $row['id']);

            if ($entity_type === 'product' && $entity_id) {
                $product = $product_model->getById($entity_id);
                if ($product) {
                    $entity_name = $product['name'];
                    if ($action === 'product.update' && $with_snapshots) {
                        $after_data = self::withWaLogId(shopUserlogProductSnapshot::captureForLog($entity_id) ?: array(), $row['id']);
                    }
                }
            } elseif ($entity_type === 'category' && $entity_id) {
                $category = $category_model->getById($entity_id);
                if ($category) {
                    $entity_name = $category['name'];
                }
            }

            $summary = self::shopSummary($row['action'], $entity_id, $entity_name);

            $event_model->log(array(
                'contact_id'   => (int) $row['contact_id'],
                'app_id'       => 'shop',
                'action'       => $action,
                'entity_type'  => $entity_type,
                'entity_id'    => $entity_id ?: null,
                'entity_name'  => $entity_name,
                'summary'      => $summary,
                'after_data'   => $after_data,
                'datetime'     => $row['datetime'],
                'can_rollback' => 0,
            ));
            if ($last_id !== null) {
                $last_id = max($last_id, (int) $row['id']);
            }
        }
    }

    protected static function parseWaLogParams($params)
    {
        if ($params && userlogHelper::isJsonString($params)) {
            return waUtils::jsonDecode($params, true);
        }
        return $params;
    }

    protected static function withWaLogId(array $data, $wa_log_id)
    {
        $data['_wa_log_id'] = (int) $wa_log_id;
        return $data;
    }

    protected static function resolveShopEntityId($params)
    {
        if (is_numeric($params)) {
            return (int) $params;
        }
        if (is_array($params)) {
            if (isset($params['id'])) {
                return (int) $params['id'];
            }
            if (isset($params['product_id'])) {
                return (int) $params['product_id'];
            }
        }
        return 0;
    }

    protected static function shopSummary($wa_action, $entity_id, $entity_name)
    {
        $name_part = $entity_name ? ' «'.$entity_name.'»' : ($entity_id ? ' #'.$entity_id : '');
        $map = array(
            'product_edit'    => 'Изменён товар'.$name_part,
            'product_add'     => 'Создан товар'.$name_part,
            'product_delete'  => 'Удалён товар'.$name_part,
            'category_add'    => 'Создана категория'.$name_part,
            'category_edit'   => 'Изменена категория'.$name_part,
            'category_delete' => 'Удалена категория'.$name_part,
        );
        return ifset($map, $wa_action, $wa_action.$name_part);
    }

    protected static function authSummary($action, $params, $row)
    {
        if ($action === 'auth.login') {
            $env = is_string($params) ? $params : ifset($params, 'source', 'backend');
            return 'Вход в админку ('.$env.')';
        }
        if ($action === 'auth.logout') {
            return 'Выход из системы';
        }
        if ($action === 'auth.login_failed') {
            $login = is_array($params) ? ifset($params, 'login', '') : '';
            return 'Неудачный вход'.($login ? ': '.$login : '');
        }
        return $row['action'];
    }
}
