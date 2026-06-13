<?php

class userlogHelper
{
    public static function getActionLabels()
    {
        return array(
            'auth.login'           => 'Вход в систему',
            'auth.logout'          => 'Выход из системы',
            'auth.login_failed'    => 'Неудачная попытка входа',
            'product.create'       => 'Создание товара',
            'product.update'       => 'Изменение товара',
            'product.delete'       => 'Удаление товара',
            'product.sort'         => 'Сортировка товаров',
            'category.create'      => 'Создание категории',
            'category.update'      => 'Изменение категории',
            'category.delete'      => 'Удаление категории',
            'category.sort'        => 'Порядок категорий',
            'category.move'        => 'Перемещение категории',
            'set.create'           => 'Создание списка',
            'set.update'           => 'Изменение списка',
            'post.create'          => 'Создание записи',
            'post.update'          => 'Изменение записи',
            'post.delete'          => 'Удаление записи',
            'order.update'         => 'Изменение заказа',
            'order.create'         => 'Создание заказа',
            'settings.update'      => 'Изменение настроек',
            'set.delete'           => 'Удаление списка',
            'rollback'             => 'Откат действия',
        );
    }

    public static function getActionIcon($action)
    {
        $map = array(
            'auth.login'        => 'user',
            'auth.logout'       => 'user',
            'auth.login_failed' => 'lock-bw',
            'product.create'    => 'add',
            'product.update'    => 'edit',
            'product.delete'    => 'delete',
            'product.sort'      => 'sort',
            'category.create'   => 'folder-add',
            'category.update'   => 'folder-edit',
            'category.delete'   => 'folder-delete',
            'category.sort'     => 'sort',
            'category.move'     => 'sort',
            'set.create'        => 'folder-add',
            'set.update'        => 'folder-edit',
            'post.create'       => 'add',
            'post.update'       => 'edit',
            'post.delete'       => 'delete',
            'order.create'      => 'add',
            'order.update'      => 'edit',
            'set.delete'        => 'folder-delete',
            'settings.update'   => 'settings',
            'rollback'          => 'undo',
        );
        return ifset($map, $action, 'info');
    }

    public static function getActionColor($action)
    {
        if (strpos($action, 'delete') !== false) {
            return '#E57373';
        }
        if (strpos($action, 'create') !== false) {
            return '#81C784';
        }
        if (strpos($action, 'auth') === 0) {
            return '#64B5F6';
        }
        if (strpos($action, 'sort') !== false) {
            return '#FFB74D';
        }
        if ($action === 'rollback') {
            return '#9575CD';
        }
        return '#7986CB';
    }

    public static function formatDiff(array $before, array $after, $entity_type = 'product')
    {
        $lines = array();
        if ($entity_type === 'post') {
            $fields = array(
                'title'            => 'Заголовок',
                'status'           => 'Статус',
                'blog'             => 'Блог',
                'url'              => 'URL',
                'datetime'         => 'Дата публикации',
                'text'             => 'Текст',
                'meta_title'       => 'Meta title',
                'meta_keywords'    => 'Meta keywords',
                'meta_description' => 'Meta description',
                'comments_allowed' => 'Комментарии',
                'params'           => 'Параметры',
            );
            $text_fields = array('text', 'meta_description');
            foreach ($fields as $key => $label) {
                $b = ifset($before, $key, null);
                $a = ifset($after, $key, null);
                if ($b !== $a && ($b !== null || $a !== null)) {
                    if (in_array($key, $text_fields, true)) {
                        $lines[] = array(
                            'field'  => $key,
                            'label'  => $label,
                            'before' => self::plainTextForDisplay((string) $b, 200),
                            'after'  => self::plainTextForDisplay((string) $a, 200),
                        );
                    } else {
                        $lines[] = array(
                            'field'  => $key,
                            'label'  => $label,
                            'before' => self::formatValue($b),
                            'after'  => self::formatValue($a),
                        );
                    }
                }
            }
            return $lines;
        }
        if ($entity_type === 'order') {
            $fields = array(
                'state'    => 'Статус',
                'total'    => 'Итого',
                'tax'      => 'Налог',
                'shipping' => 'Доставка',
                'discount' => 'Скидка',
                'currency' => 'Валюта',
                'comment'  => 'Комментарий',
                'items'    => 'Состав заказа',
            );
            $text_fields = array('comment', 'items');
            foreach ($fields as $key => $label) {
                $b = ifset($before, $key, null);
                $a = ifset($after, $key, null);
                if ($b !== $a && ($b !== null || $a !== null)) {
                    if (in_array($key, $text_fields, true)) {
                        $lines[] = array(
                            'field'  => $key,
                            'label'  => $label,
                            'before' => self::plainTextForDisplay((string) $b, 200),
                            'after'  => self::plainTextForDisplay((string) $a, 200),
                        );
                    } else {
                        $lines[] = array(
                            'field'  => $key,
                            'label'  => $label,
                            'before' => self::formatValue($b),
                            'after'  => self::formatValue($a),
                        );
                    }
                }
            }
            return $lines;
        }
        if ($entity_type === 'category') {
            $fields = array(
                'name'                   => 'Название',
                'url'                    => 'URL',
                'description'            => 'Описание',
                'meta_title'             => 'Meta title',
                'meta_keywords'          => 'Meta keywords',
                'meta_description'       => 'Meta description',
                'type'                   => 'Тип',
                'status'                 => 'Видимость',
                'parent'                 => 'Родитель',
                'sort_products'          => 'Сортировка товаров',
                'include_sub_categories' => 'Включать подкатегории',
                'filter'                 => 'Фильтр',
                'conditions'             => 'Условия (динам.)',
                'params'                 => 'Параметры',
                'routes'                 => 'Витрины',
                'og'                     => 'Open Graph',
            );
            $text_fields = array('description', 'meta_description', 'conditions');
            foreach ($fields as $key => $label) {
                $b = ifset($before, $key, null);
                $a = ifset($after, $key, null);
                if ($b !== $a && ($b !== null || $a !== null)) {
                    if (in_array($key, $text_fields, true)) {
                        $lines[] = array(
                            'field'  => $key,
                            'label'  => $label,
                            'before' => self::plainTextForDisplay((string) $b, 200),
                            'after'  => self::plainTextForDisplay((string) $a, 200),
                        );
                    } else {
                        $lines[] = array(
                            'field'  => $key,
                            'label'  => $label,
                            'before' => self::formatValue($b),
                            'after'  => self::formatValue($a),
                        );
                    }
                }
            }
            return $lines;
        }
        if ($entity_type === 'set') {
            $fields = array(
                'name'        => 'Название',
                'id'          => 'ID списка',
                'type'        => 'Тип',
                'status'      => 'Видимость',
                'rule'        => 'Правило (динам.)',
                'count'       => 'Кол-во товаров',
            );
            foreach ($fields as $key => $label) {
                $b = ifset($before, $key, null);
                $a = ifset($after, $key, null);
                if ($b !== $a && ($b !== null || $a !== null)) {
                    $lines[] = array(
                        'field'  => $key,
                        'label'  => $label,
                        'before' => self::formatValue($b),
                        'after'  => self::formatValue($a),
                    );
                }
            }
            return $lines;
        }
        if ($entity_type !== 'product') {
            return $lines;
        }

        $fields = array(
            'name'             => 'Название',
            'summary'          => 'Краткое описание',
            'description'      => 'Описание',
            'meta_title'       => 'Meta title',
            'meta_keywords'    => 'Meta keywords',
            'meta_description' => 'Meta description',
            'url'              => 'URL',
            'type'             => 'Тип товара',
            'price'            => 'Цена (товар)',
            'compare_price'    => 'Зачёркнутая цена',
            'min_price'        => 'Мин. цена',
            'max_price'        => 'Макс. цена',
            'currency'         => 'Валюта',
            'status'           => 'Статус',
            'count'            => 'Остаток (товар)',
            'sku_type'         => 'Тип SKU',
            'sku'              => 'Артикул (основной)',
            'categories'       => 'Категории',
            'sets'             => 'Списки',
            'tags'             => 'Теги',
            'params'           => 'Доп. параметры',
            'images'           => 'Изображения',
        );
        $text_fields = array('summary', 'description', 'meta_description');
        foreach ($fields as $key => $label) {
            $b = ifset($before, $key, null);
            $a = ifset($after, $key, null);
            if ($b !== $a && ($b !== null || $a !== null)) {
                if (in_array($key, $text_fields, true)) {
                    $lines[] = array(
                        'field'  => $key,
                        'label'  => $label,
                        'before' => self::plainTextForDisplay((string) $b, 200),
                        'after'  => self::plainTextForDisplay((string) $a, 200),
                    );
                } else {
                    $lines[] = array(
                        'field'  => $key,
                        'label'  => $label,
                        'before' => self::formatValue($b),
                        'after'  => self::formatValue($a),
                    );
                }
            }
        }

        $b_feat = ifset($before, 'features', array());
        $a_feat = ifset($after, 'features', array());
        if ($b_feat || $a_feat) {
            wa('shop');
            $feature_codes = array_unique(array_merge(array_keys($b_feat), array_keys($a_feat)));
            sort($feature_codes, SORT_STRING);
            $feature_labels = array();
            if ($feature_codes) {
                foreach ((new shopFeatureModel())->getByCode($feature_codes) as $code => $feature) {
                    $feature_labels[$code] = ifset($feature, 'name', $code);
                }
            }
            foreach ($feature_codes as $code) {
                $bv = ifset($b_feat, $code, '');
                $av = ifset($a_feat, $code, '');
                if ((string) $bv !== (string) $av) {
                    $feat_label = ifset($feature_labels, $code, $code);
                    $lines[] = array(
                        'field'  => 'feature_'.$code,
                        'label'  => 'Характеристика «'.$feat_label.'»',
                        'before' => self::formatValue($bv !== '' ? $bv : null),
                        'after'  => self::formatValue($av !== '' ? $av : null),
                    );
                }
            }
        }

        $b_skus = ifset($before, 'skus', array());
        $a_skus = ifset($after, 'skus', array());
        $sku_ids = array_unique(array_merge(array_keys($b_skus), array_keys($a_skus)));
        sort($sku_ids, SORT_NUMERIC);

        foreach ($sku_ids as $sid) {
            $b = ifset($b_skus, $sid, array());
            $a = ifset($a_skus, $sid, array());
            $label = ifset($a, 'label', ifset($b, 'label', 'SKU #'.$sid));
            foreach (array(
                'name'           => 'Название SKU',
                'price'          => 'Цена',
                'compare_price'  => 'Зачёркнутая цена SKU',
                'purchase_price' => 'Закупочная цена',
                'sku'            => 'Артикул SKU',
                'available'      => 'Доступность',
                'status'         => 'Статус SKU',
                'count'          => 'Остаток SKU',
            ) as $field => $field_label) {
                $bv = ifset($b, $field, null);
                $av = ifset($a, $field, null);
                if ($bv !== $av && ($bv !== null || $av !== null)) {
                    $lines[] = array(
                        'field'  => 'sku_'.$sid.'_'.$field,
                        'label'  => $field_label.' «'.$label.'»',
                        'before' => self::formatValue($bv),
                        'after'  => self::formatValue($av),
                    );
                }
            }
        }

        $b_sort = ifset($before, 'category_sort', array());
        $a_sort = ifset($after, 'category_sort', array());
        $cat_ids = array_unique(array_merge(array_keys($b_sort), array_keys($a_sort)));
        sort($cat_ids, SORT_NUMERIC);
        foreach ($cat_ids as $cid) {
            $bv = ifset($b_sort, $cid, null);
            $av = ifset($a_sort, $cid, null);
            if ($bv !== $av) {
                $lines[] = array(
                    'field'  => 'category_sort_'.$cid,
                    'label'  => 'Позиция в категории #'.$cid,
                    'before' => self::formatValue($bv),
                    'after'  => self::formatValue($av),
                );
            }
        }

        return $lines;
    }

    public static function formatCategoryTreeDiff(array $before, array $after)
    {
        $lines = array();
        $ids = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($ids, SORT_NUMERIC);
        foreach ($ids as $id) {
            $b = ifset($before, $id, array());
            $a = ifset($after, $id, array());
            if (!$b || !$a) {
                continue;
            }
            $name = ifset($a, 'name', ifset($b, 'name', '#'.$id));
            $bp = (int) ifset($b, 'parent_id', 0);
            $ap = (int) ifset($a, 'parent_id', 0);
            $bs = (int) ifset($b, 'sort', 0);
            $as = (int) ifset($a, 'sort', 0);
            if ($bp !== $ap) {
                $lines[] = array(
                    'field'  => 'parent_'.$id,
                    'label'  => '«'.$name.'»: родитель',
                    'before' => $bp ? '#'.$bp : 'Корень',
                    'after'  => $ap ? '#'.$ap : 'Корень',
                );
            }
            if ($bs !== $as) {
                $lines[] = array(
                    'field'  => 'sort_'.$id,
                    'label'  => '«'.$name.'»: порядок',
                    'before' => self::formatValue($bs),
                    'after'  => self::formatValue($as),
                );
            }
        }
        return $lines;
    }

    public static function formatSortDiff(array $before, array $after, array $names = array())
    {
        $lines = array();
        $ids = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($ids, SORT_NUMERIC);
        foreach ($ids as $pid) {
            $b = ifset($before, $pid, null);
            $a = ifset($after, $pid, null);
            if ($b === $a) {
                continue;
            }
            $name = ifset($names, $pid, ifset($names, (string) $pid, ''));
            $label = $name ? $name.' (#'.$pid.')' : 'Товар #'.$pid;
            $lines[] = array(
                'field'  => 'sort_'.$pid,
                'label'  => $label,
                'before' => self::formatValue($b),
                'after'  => self::formatValue($a),
            );
        }
        return $lines;
    }

    public static function flattenSetForDiff(array $snapshot)
    {
        $set = ifset($snapshot, 'set', $snapshot);
        $type = (int) ifset($set, 'type', 0);
        return array(
            'name'   => ifset($set, 'name', ''),
            'id'     => ifset($set, 'id', ''),
            'type'   => $type === shopSetModel::TYPE_DYNAMIC ? 'Динамический' : 'Статический',
            'status' => (int) ifset($set, 'status', 1) ? 'Видим' : 'Скрыт',
            'rule'   => ifset($set, 'rule', ''),
            'count'  => ifset($set, 'count', ''),
        );
    }

    public static function formatCategoryMoveDiff(array $before, array $after)
    {
        $lines = array();
        $fields = array(
            'parent_name' => 'Родительская категория',
            'sort'        => 'Порядок',
        );
        foreach ($fields as $key => $label) {
            $b = ifset($before, $key, null);
            $a = ifset($after, $key, null);
            if ($key === 'parent_name') {
                if (!(int) ifset($before, 'parent_id', 0)) {
                    $b = 'Корень';
                }
                if (!(int) ifset($after, 'parent_id', 0)) {
                    $a = 'Корень';
                }
            }
            if ($b !== $a && ($b !== null || $a !== null)) {
                $lines[] = array(
                    'field'  => $key,
                    'label'  => $label,
                    'before' => self::formatValue($b),
                    'after'  => self::formatValue($a),
                );
            }
        }
        return $lines;
    }

    /**
     * Plain text for diff cards: strip markup, decode entities, collapse whitespace.
     */
    public static function plainTextForDisplay($text, $max_length = 0)
    {
        if (!is_string($text) || $text === '') {
            return (string) $text;
        }
        if (strpos($text, '<') !== false) {
            $text = strip_tags($text);
        }
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));
        if ($max_length > 0 && mb_strlen($text) > $max_length) {
            return mb_substr($text, 0, $max_length).'…';
        }
        return $text;
    }

    protected static function formatValue($value)
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'да' : 'нет';
        }
        if (is_array($value)) {
            return waUtils::jsonEncode($value);
        }
        return self::plainTextForDisplay((string) $value);
    }

    public static function isJsonString($string)
    {
        if (!is_string($string) || $string === '') {
            return false;
        }
        $string = trim($string);
        if ($string[0] !== '{' && $string[0] !== '[') {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public static function parseSummaryDiff($summary)
    {
        $lines = array();
        if (!is_string($summary) || strpos($summary, '→') === false) {
            return $lines;
        }
        $tail = $summary;
        if (preg_match('/ — (.+)$/u', $summary, $m)) {
            $tail = $m[1];
        }
        foreach (preg_split('/;\s*/u', $tail) as $part) {
            $part = trim($part);
            if (preg_match('/^(.+?):\s*(.+?)\s*→\s*(.+)$/u', $part, $mm)) {
                $lines[] = array(
                    'field'  => 'summary',
                    'label'  => trim($mm[1]),
                    'before' => self::formatValue(trim($mm[2])),
                    'after'  => self::formatValue(trim($mm[3])),
                );
            }
        }
        return $lines;
    }

    /**
     * Prepare event row for timeline card (diff, title, rollback id).
     */
    public static function enrichEvent(array $event, userlogEventModel $model = null)
    {
        if (!$model) {
            $model = new userlogEventModel();
        }

        $event['rollback_event_id'] = (int) $event['id'];

        if (empty($event['before_data']) && $event['action'] === 'post.update') {
            $richer = $model->findRicherDuplicate($event);
            if ($richer) {
                $event['rollback_event_id'] = (int) $richer['id'];
                $event['can_rollback'] = $richer['can_rollback'];
                $event['rolled_back'] = $richer['rolled_back'];
                if (empty($event['before_data'])) {
                    $event['before_data'] = $richer['before_data'];
                }
                if (empty($event['after_data'])) {
                    $event['after_data'] = $richer['after_data'];
                }
                if (strpos((string) $event['summary'], '→') === false) {
                    $event['summary'] = $richer['summary'];
                }
                if (!empty($richer['rolled_back'])) {
                    $event['rolled_back'] = $richer['rolled_back'];
                    $event['rolled_back_at'] = $richer['rolled_back_at'];
                    $event['rolled_back_by'] = $richer['rolled_back_by'];
                }
            }
        }

        if (empty($event['before_data']) && $event['action'] === 'product.update') {
            $richer = $model->findRicherDuplicate($event);
            if ($richer) {
                $event['rollback_event_id'] = (int) $richer['id'];
                $event['can_rollback'] = $richer['can_rollback'];
                $event['rolled_back'] = $richer['rolled_back'];
                if (empty($event['before_data'])) {
                    $event['before_data'] = $richer['before_data'];
                }
                if (empty($event['after_data'])) {
                    $event['after_data'] = $richer['after_data'];
                }
                if (strpos((string) $event['summary'], '→') === false) {
                    $event['summary'] = $richer['summary'];
                }
                if (!empty($richer['rolled_back'])) {
                    $event['rolled_back'] = $richer['rolled_back'];
                    $event['rolled_back_at'] = $richer['rolled_back_at'];
                    $event['rolled_back_by'] = $richer['rolled_back_by'];
                }
            }
        }

        if (!empty($event['rolled_back']) && !empty($event['rolled_back_by']) && empty($event['rolled_back_by_name'])) {
            $event['rolled_back_by_name'] = (new waContact((int) $event['rolled_back_by']))->getName();
        }

        if ($event['action'] === 'rollback') {
            $after = self::decodeEventData($event, 'after_data');
            if (is_array($after) && !empty($after['rolled_back_event_id'])) {
                $event['rollback_of_event_id'] = (int) $after['rolled_back_event_id'];
            }
            $before_snapshot = self::decodeEventData($event, 'before_data');
            $restored = is_array($after) && !empty($after['restored']) ? $after['restored'] : null;
            if (is_array($before_snapshot) && is_array($restored) && wa()->appExists('shop')) {
                wa('shop');
                $event['diff'] = self::formatDiff(
                    shopUserlogProductSnapshot::flattenForDiff($before_snapshot),
                    shopUserlogProductSnapshot::flattenForDiff($restored),
                    'product'
                );
            } elseif (is_array($before_snapshot) && is_array($restored) && wa()->appExists('blog')) {
                wa('blog');
                if (class_exists('blogPlugin')) {
                    wa('blog')->getPlugin('userlog');
                }
                if (class_exists('blogUserlogPostSnapshot')) {
                    $event['diff'] = self::formatDiff(
                        blogUserlogPostSnapshot::flattenForDiff($before_snapshot),
                        blogUserlogPostSnapshot::flattenForDiff($restored),
                        'post'
                    );
                }
            } elseif (!empty($event['summary'])) {
                $event['diff'] = self::parseSummaryDiff($event['summary']);
            }

            if (!empty($event['entity_name'])) {
                $event['title'] = $event['entity_name'];
            } elseif (preg_match('/«([^»]+)»/u', (string) $event['summary'], $m)) {
                $event['title'] = $m[1];
            } else {
                $event['title'] = $event['summary'];
            }

            $event['entity_url'] = self::getEntityBackendUrl($event['entity_type'], $event['entity_id']);

            return $event;
        }

        $before = self::decodeEventData($event, 'before_data');
        $after = self::decodeEventData($event, 'after_data');
        $event['diff'] = self::buildEventDiff($event['action'], $before, $after, $event['summary']);

        if (!$event['diff'] && $event['action'] === 'post.update' && !empty($event['entity_id'])) {
            $event['diff'] = self::inferPostUpdateDiff($event, $model);
            if ($event['diff'] && strpos((string) $event['summary'], '→') === false) {
                $title = ifset($event, 'entity_name', 'запись');
                $parts = array();
                foreach (array_slice($event['diff'], 0, 5) as $line) {
                    $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
                }
                $event['summary'] = 'Изменена «'.$title.'» — '.implode('; ', $parts);
            }
        }

        if (empty($before) && $event['action'] === 'post.update' && $event['diff'] && empty($event['can_rollback'])) {
            $partial_before = self::buildPartialPostBeforeFromDiff($event, $model);
            if ($partial_before) {
                $before = $partial_before;
                $event['before_data'] = $partial_before;
                $event['can_rollback'] = 1;
            }
        }

        if (in_array($event['action'], array('category.move', 'category.sort'), true)
            && $event['diff'] && empty($event['can_rollback']) && !empty($before)
        ) {
            $event['can_rollback'] = 1;
        }

        if ($event['can_rollback'] && empty($event['rolled_back']) && $event['diff']) {
            $event['restore_preview'] = self::buildRestorePreview($event['diff']);
            $event['restore_preview_text'] = implode('; ', $event['restore_preview']);
        } elseif ($event['can_rollback'] && empty($event['rolled_back'])
            && in_array($event['action'], array('product.delete', 'category.delete'), true)
        ) {
            $label = $event['action'] === 'category.delete' ? 'Категория' : 'Товар';
            $name = $event['entity_name'] ?: '#'.$event['entity_id'];
            $event['restore_preview'] = array($label.' «'.$name.'» будет восстановлен из корзины');
            $event['restore_preview_text'] = $event['restore_preview'][0];
        }

        if (!empty($event['entity_name'])) {
            $event['title'] = $event['entity_name'];
        } elseif (preg_match('/«([^»]+)»/u', (string) $event['summary'], $m)) {
            $event['title'] = $m[1];
        } else {
            $event['title'] = $event['summary'];
        }

        $event['entity_url'] = self::getEntityBackendUrl($event['entity_type'], $event['entity_id']);

        return $event;
    }

    /**
     * Admin URL to open the changed object (product, category, blog post).
     */
    public static function getEntityBackendUrl($entity_type, $entity_id)
    {
        $entity_id = (int) $entity_id;
        if (!$entity_id || !$entity_type) {
            return null;
        }

        $backend_url = rtrim(wa()->getConfig()->getBackendUrl(true), '/').'/';

        switch ($entity_type) {
            case 'product':
                if (wa()->appExists('shop')) {
                    return $backend_url.'shop/?action=products#/product/'.$entity_id.'/';
                }
                break;
            case 'category':
                if (wa()->appExists('shop')) {
                    return $backend_url.'shop/?action=products#/products/category_id='.$entity_id.'&view=table';
                }
                break;
            case 'post':
                if (wa()->appExists('blog')) {
                    return $backend_url.'blog/?module=post&action=edit&id='.$entity_id;
                }
                break;
            case 'order':
                if (wa()->appExists('shop')) {
                    return $backend_url.'shop/?action=orders#/order/'.$entity_id.'/';
                }
                break;
        }

        return null;
    }

    /**
     * Human-readable list of values that rollback will apply (current → restored).
     */
    public static function buildRestorePreview(array $diff)
    {
        $lines = array();
        foreach ($diff as $line) {
            $lines[] = $line['label'].': '.$line['after'].' → '.$line['before'];
        }
        return $lines;
    }

    protected static function decodeEventData(array $event, $field)
    {
        if (empty($event[$field])) {
            return null;
        }
        if (is_array($event[$field])) {
            return $event[$field];
        }
        if (self::isJsonString($event[$field])) {
            return waUtils::jsonDecode($event[$field], true);
        }
        return null;
    }

    public static function decodeEventDataForRollback(array $event, $field)
    {
        $data = self::decodeEventData($event, $field);
        return is_array($data) ? $data : array();
    }

    protected static function inferPostUpdateDiff(array $event, userlogEventModel $model)
    {
        if (empty($event['entity_id']) || empty($event['entity_name'])) {
            return array();
        }
        $prev = $model->findPreviousEntityEvent(
            (int) $event['entity_id'],
            'blog',
            $event['datetime'],
            (int) $event['id']
        );
        if (!$prev || empty($prev['entity_name'])) {
            return array();
        }
        $before_title = trim((string) $prev['entity_name']);
        $after_title = trim((string) $event['entity_name']);
        if ($before_title === '' || $after_title === '' || $before_title === $after_title) {
            return array();
        }
        return array(array(
            'field'  => 'title',
            'label'  => 'Заголовок',
            'before' => self::formatValue($before_title),
            'after'  => self::formatValue($after_title),
        ));
    }

    /**
     * Partial rollback for wa_log imports: title-only when previous journal entry exists.
     */
    protected static function buildPartialPostBeforeFromDiff(array $event, userlogEventModel $model)
    {
        if (empty($event['diff']) || empty($event['entity_id'])) {
            return null;
        }
        $fields = array();
        foreach ($event['diff'] as $line) {
            if (!empty($line['field'])) {
                $fields[] = $line['field'];
            }
        }
        $fields = array_unique($fields);
        if ($fields !== array('title')) {
            return null;
        }

        $prev = $model->findPreviousEntityEvent(
            (int) $event['entity_id'],
            'blog',
            $event['datetime'],
            (int) $event['id']
        );
        if (!$prev || empty($prev['entity_name'])) {
            return null;
        }

        return array(
            'post'              => array('title' => $prev['entity_name']),
            '_partial_restore'  => array('title'),
        );
    }

    public static function buildEventDiff($action, $before, $after, $summary = '')
    {
        $diff = array();
        if (wa()->appExists('shop')) {
            wa('shop');
            if ($action === 'product.update' && is_array($before) && is_array($after)) {
                $diff = self::formatDiff(
                    shopUserlogProductSnapshot::flattenForDiff($before),
                    shopUserlogProductSnapshot::flattenForDiff($after),
                    'product'
                );
            } elseif ($action === 'product.sort' && is_array($before) && is_array($after)) {
                $diff = self::formatSortDiff(
                    ifset($before, 'items', array()),
                    ifset($after, 'items', array()),
                    ifset($after, 'names', array())
                );
            } elseif (in_array($action, array('category.move', 'category.sort'), true)
                && is_array($before) && is_array($after)
                && !empty($before['id'])
            ) {
                $diff = self::formatCategoryMoveDiff($before, $after);
            } elseif ($action === 'category.update' && is_array($before) && is_array($after)
                && class_exists('shopUserlogCategorySnapshot')
            ) {
                $diff = self::formatDiff(
                    shopUserlogCategorySnapshot::flattenForDiff($before),
                    shopUserlogCategorySnapshot::flattenForDiff($after),
                    'category'
                );
            } elseif ($action === 'order.update' && is_array($before) && is_array($after)
                && class_exists('shopUserlogOrderSnapshot')
                && !empty($before['order'])
            ) {
                $diff = self::formatDiff(
                    shopUserlogOrderSnapshot::flattenForDiff($before),
                    shopUserlogOrderSnapshot::flattenForDiff($after),
                    'order'
                );
            } elseif ($action === 'category.sort' && is_array($before) && is_array($after)
                && !empty($before['tree']) && !empty($after['tree'])
            ) {
                $diff = self::formatCategoryTreeDiff($before['tree'], $after['tree']);
            } elseif ($action === 'set.update' && is_array($before) && is_array($after)) {
                $diff = self::formatDiff(
                    self::flattenSetForDiff($before),
                    self::flattenSetForDiff($after),
                    'set'
                );
            }
        }
        if (!$diff && wa()->appExists('blog') && $action === 'post.update' && is_array($before) && is_array($after)) {
            wa('blog');
            if (class_exists('blogPlugin')) {
                wa('blog')->getPlugin('userlog');
            }
            if (class_exists('blogUserlogPostSnapshot')) {
                $diff = self::formatDiff(
                    blogUserlogPostSnapshot::flattenForDiff($before),
                    blogUserlogPostSnapshot::flattenForDiff($after),
                    'post'
                );
            }
        }
        if (!$diff && $summary) {
            $diff = self::parseSummaryDiff($summary);
        }
        return $diff;
    }

    public static function ensureAppLoaded()
    {
        if (!wa()->appExists('userlog')) {
            return false;
        }
        wa('userlog');
        return true;
    }

    public static function trashStoragePath($trash_id)
    {
        return wa()->getDataPath('trash/'.$trash_id.'/', true, 'userlog');
    }
}
