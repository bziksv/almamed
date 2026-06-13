<?php

class userlogRollbackService
{
    public function rollbackEvent($event_id)
    {
        $event_model = new userlogEventModel();
        $event = $event_model->getById($event_id);
        if (!$event) {
            throw new waException('Событие не найдено');
        }

        $event = userlogHelper::enrichEvent($event, $event_model);

        if (empty($event['can_rollback']) || !empty($event['rolled_back'])) {
            throw new waException('Откат для этого действия недоступен');
        }
        if (!wa()->getUser()->isAdmin('userlog')
            && !wa()->getUser()->isAdmin('webasyst')
            && !wa()->getUser()->getRights('userlog', 'rollback')
        ) {
            throw new waRightsException('Нет прав на откат');
        }

        $before = userlogHelper::decodeEventDataForRollback($event, 'before_data');
        $result_id = null;
        $rollback_log = null;

        switch ($event['action']) {
            case 'product.update':
                $rollback_log = $this->rollbackProductUpdate((int) $event['entity_id'], $before);
                $result_id = $rollback_log['product_id'];
                break;
            case 'product.delete':
            case 'category.delete':
                if ($event['trash_id']) {
                    wa('shop');
                    shopUserlogPlugin::setLoggingSuspended(true);
                    try {
                        $result_id = (new userlogTrashService())->restore($event['trash_id']);
                    } finally {
                        shopUserlogPlugin::setLoggingSuspended(false);
                    }
                } else {
                    throw new waException('Данные для восстановления не найдены');
                }
                break;
            case 'product.sort':
                $this->rollbackProductSort($before);
                break;
            case 'category.move':
            case 'category.sort':
                wa('shop');
                shopUserlogPlugin::setLoggingSuspended(true);
                try {
                    if (!empty($before['tree'])) {
                        $this->rollbackCategoryTree($before['tree']);
                        $result_id = 0;
                    } else {
                        $result_id = $this->rollbackCategoryMove((int) $event['entity_id'], $before);
                    }
                } finally {
                    shopUserlogPlugin::setLoggingSuspended(false);
                }
                break;
            case 'category.update':
                wa('shop');
                shopUserlogPlugin::setLoggingSuspended(true);
                try {
                    $result_id = shopUserlogCategorySnapshot::restoreForUpdate(
                        $before,
                        (int) $event['entity_id']
                    );
                } finally {
                    shopUserlogPlugin::setLoggingSuspended(false);
                }
                break;
            case 'post.update':
                wa('blog');
                wa('blog')->getPlugin('userlog');
                blogUserlogPlugin::setLoggingSuspended(true);
                try {
                    $rollback_log = $this->rollbackPostUpdate((int) $event['entity_id'], $before);
                    $result_id = $rollback_log['post_id'];
                } finally {
                    blogUserlogPlugin::setLoggingSuspended(false);
                }
                break;
            case 'post.delete':
                wa('blog');
                wa('blog')->getPlugin('userlog');
                blogUserlogPlugin::setLoggingSuspended(true);
                try {
                    $rollback_log = $this->rollbackPostDelete($before);
                    $result_id = $rollback_log['post_id'];
                } finally {
                    blogUserlogPlugin::setLoggingSuspended(false);
                }
                break;
            case 'order.update':
                wa('shop');
                shopUserlogPlugin::setLoggingSuspended(true);
                try {
                    if (!empty($before['order'])) {
                        $rollback_log = $this->rollbackOrderUpdate((int) $event['entity_id'], $before);
                        $result_id = $rollback_log['order_id'];
                    } elseif (array_key_exists('state_id', $before)) {
                        $result_id = shopUserlogOrderSnapshot::restoreState(
                            (int) $event['entity_id'],
                            $before['state_id']
                        );
                    } else {
                        throw new waException('Нет данных для отката заказа');
                    }
                } finally {
                    shopUserlogPlugin::setLoggingSuspended(false);
                }
                break;
            default:
                throw new waException('Откат для данного типа действия пока не поддерживается');
        }

        $event_model->updateById($event_id, array(
            'rolled_back'    => 1,
            'rolled_back_at' => date('Y-m-d H:i:s'),
            'rolled_back_by' => wa()->getUser()->getId(),
        ));

        userlogLogger::log(array(
            'app_id'      => 'userlog',
            'action'      => 'rollback',
            'entity_type' => $event['entity_type'],
            'entity_id'   => $event['entity_id'],
            'entity_name' => $event['entity_name'],
            'summary'     => sprintf(
                'Откат действия #%d от %s — %s',
                $event_id,
                waDateTime::format('humandatetime', $event['datetime']),
                $event['summary']
            ),
            'before_data' => $rollback_log ? $rollback_log['log_before'] : null,
            'after_data'  => array(
                'rolled_back_event_id' => $event_id,
                'restored_id'          => $result_id,
                'restored'             => $rollback_log ? $rollback_log['log_after'] : null,
            ),
            'can_rollback'=> 0,
        ));

        $message = 'Действие успешно отменено';
        if (in_array($event['action'], array('category.delete', 'product.delete'), true)) {
            $message = $this->rollbackDeleteMessage($event, $result_id);
        } elseif ($event['action'] === 'post.update') {
            $message = sprintf(
                'Запись «%s» восстановлена. Обновите страницу редактора блога.',
                $event['entity_name'] ?: '#'.$result_id
            );
        } elseif ($event['action'] === 'post.delete') {
            $message = sprintf(
                'Запись «%s» восстановлена (#%d). Откройте её в редакторе блога.',
                $event['entity_name'] ?: 'запись',
                (int) $result_id
            );
        } elseif ($event['action'] === 'order.update') {
            $message = sprintf(
                'Заказ #%d восстановлен. Обновите страницу заказа.',
                (int) $result_id
            );
        } elseif (in_array($event['action'], array('category.move', 'category.sort'), true)) {
            if (!empty($before['tree'])) {
                $message = 'Порядок категорий в дереве восстановлен. Обновите страницу магазина.';
            } else {
                $message = sprintf(
                    'Категория «%s» возвращена в %s. Обновите страницу магазина.',
                    $event['entity_name'] ?: '#'.$result_id,
                    $this->formatCategoryParentLabel($before)
                );
            }
        } elseif ($event['action'] === 'category.update') {
            $message = sprintf(
                'Категория «%s» восстановлена. Обновите страницу магазина.',
                $event['entity_name'] ?: '#'.$result_id
            );
        }

        return array(
            'entity_id' => $result_id,
            'message'   => $message,
        );
    }

    protected function rollbackDeleteMessage($event, $result_id)
    {
        if ($event['action'] === 'category.delete') {
            return sprintf(
                'Категория «%s» восстановлена (ID %d). Обновите страницу магазина, чтобы увидеть её в списке.',
                $event['entity_name'],
                $result_id
            );
        }
        if ($event['action'] === 'product.delete') {
            return sprintf('Товар «%s» восстановлен (ID %d).', $event['entity_name'], $result_id);
        }
        return 'Действие успешно отменено';
    }

    protected function rollbackOrderUpdate($order_id, array $before)
    {
        if (!$order_id || !$before) {
            throw new waException('Нет данных для отката заказа');
        }

        $log_before = shopUserlogOrderSnapshot::captureForLog($order_id);
        $result_id = shopUserlogOrderSnapshot::restoreForUpdate($before, $order_id);
        $log_after = shopUserlogOrderSnapshot::captureForLog($order_id);

        return array(
            'order_id'   => $result_id,
            'log_before' => $log_before,
            'log_after'  => $log_after,
        );
    }

    protected function rollbackProductUpdate($product_id, array $before)
    {
        if (!$product_id || !$before) {
            throw new waException('Нет данных для отката');
        }

        wa('shop');
        $log_before = shopUserlogProductSnapshot::captureForLog($product_id);

        shopUserlogPlugin::setLoggingSuspended(true);
        try {
            $result_id = shopUserlogProductSnapshot::restore(
                shopUserlogProductSnapshot::prepareForRestore($before, $product_id),
                null,
                $product_id
            );
        } finally {
            shopUserlogPlugin::setLoggingSuspended(false);
        }

        $log_after = shopUserlogProductSnapshot::captureForLog($product_id);

        return array(
            'product_id' => $result_id,
            'log_before' => $log_before,
            'log_after'  => $log_after,
        );
    }

    protected function rollbackProductSort(array $before)
    {
        if (empty($before['items']) || empty($before['category_id'])) {
            throw new waException('Нет данных сортировки');
        }
        wa('shop');
        $model = new shopCategoryProductsModel();
        foreach ($before['items'] as $product_id => $sort) {
            $model->updateByField(
                array('category_id' => (int) $before['category_id'], 'product_id' => (int) $product_id),
                array('sort' => (int) $sort)
            );
        }
    }

    protected function rollbackPostUpdate($post_id, array $before)
    {
        if (!$post_id || !$before) {
            throw new waException('Нет данных для отката');
        }

        wa('blog');
        wa('blog')->getPlugin('userlog');

        $log_before = blogUserlogPostSnapshot::captureForLog($post_id);
        $result_id = blogUserlogPostSnapshot::restore(
            blogUserlogPostSnapshot::prepareForRestore($before, $post_id),
            $post_id
        );
        $log_after = blogUserlogPostSnapshot::captureForLog($post_id);

        return array(
            'post_id'    => $result_id,
            'log_before' => $log_before,
            'log_after'  => $log_after,
        );
    }

    protected function rollbackPostDelete(array $before)
    {
        if (!$before) {
            throw new waException('Нет данных для восстановления записи');
        }

        wa('blog');
        wa('blog')->getPlugin('userlog');

        $post_id = blogUserlogPostSnapshot::restoreFromDelete($before);
        $log_after = blogUserlogPostSnapshot::captureForLog($post_id);

        return array(
            'post_id'    => $post_id,
            'log_before' => null,
            'log_after'  => $log_after,
        );
    }

    protected function rollbackCategoryMove($category_id, array $before)
    {
        if (!$category_id || !$before) {
            throw new waException('Нет данных для отката');
        }

        $model = new shopCategoryModel();
        if (!$model->getById($category_id)) {
            throw new waException('Категория не найдена');
        }

        $parent_id = (int) ifset($before, 'parent_id', 0);
        if (!$model->move($category_id, null, $parent_id)) {
            throw new waException('Не удалось переместить категорию');
        }

        return $category_id;
    }

    protected function rollbackCategoryTree(array $tree)
    {
        if (!$tree) {
            throw new waException('Нет данных дерева категорий');
        }
        $model = new shopCategoryModel();
        foreach ($tree as $category_id => $row) {
            $category_id = (int) $category_id;
            if (!$category_id || !is_array($row)) {
                continue;
            }
            $model->updateById($category_id, array(
                'parent_id' => (int) ifset($row, 'parent_id', 0),
                'sort'      => (int) ifset($row, 'sort', 0),
            ));
        }
        $model->repair();
    }

    protected function formatCategoryParentLabel(array $snapshot)
    {
        $parent_name = trim((string) ifset($snapshot, 'parent_name', ''));
        if ($parent_name !== '') {
            return '«'.$parent_name.'»';
        }
        return (int) ifset($snapshot, 'parent_id', 0) ? '#'.(int) $snapshot['parent_id'] : 'Корень';
    }
}
