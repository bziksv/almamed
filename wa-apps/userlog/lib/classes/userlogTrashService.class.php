<?php

class userlogTrashService
{
    public function trashProducts(array $product_ids)
    {
        if (!userlogHelper::ensureAppLoaded() || !$product_ids) {
            return array();
        }

        $trash_ids = array();
        foreach ($product_ids as $product_id) {
            $product_id = (int) $product_id;
            if (!$product_id) {
                continue;
            }
            $snapshot = shopUserlogProductSnapshot::capture($product_id);
            if (!$snapshot) {
                continue;
            }

            $trash_model = new userlogTrashModel();
            $trash_id = $trash_model->addItem(array(
                'entity_type' => 'product',
                'original_id' => $product_id,
                'name'        => ifset($snapshot, 'product', 'name', 'Товар #'.$product_id),
                'snapshot'    => $snapshot,
                'deleted_by'  => wa()->getUser()->getId(),
            ));

            $files_path = userlogHelper::trashStoragePath($trash_id);
            shopUserlogProductSnapshot::copyFiles($product_id, $files_path);
            $trash_model->updateById($trash_id, array('files_path' => $files_path));

            $event_id = userlogLogger::log(array(
                'app_id'       => 'shop',
                'action'       => 'product.delete',
                'entity_type'  => 'product',
                'entity_id'    => $product_id,
                'entity_name'  => ifset($snapshot, 'product', 'name', ''),
                'summary'      => 'Удалён товар «'.ifset($snapshot, 'product', 'name', '#'.$product_id).'»',
                'before_data'  => $snapshot,
                'can_rollback' => 1,
                'trash_id'     => $trash_id,
            ));

            if ($event_id) {
                $trash_model->updateById($trash_id, array('event_id' => $event_id));
            }

            $trash_ids[$product_id] = $trash_id;
        }

        return $trash_ids;
    }

    public function trashCategory($category_id)
    {
        $category_id = (int) $category_id;
        if (!$category_id || !userlogHelper::ensureAppLoaded()) {
            return null;
        }

        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        if (!$category) {
            return null;
        }

        $snapshot = shopUserlogCategorySnapshot::capture($category_id);
        $trash_model = new userlogTrashModel();
        $trash_id = $trash_model->addItem(array(
            'entity_type' => 'category',
            'original_id' => $category_id,
            'name'        => $category['name'],
            'snapshot'    => $snapshot,
            'deleted_by'  => wa()->getUser()->getId(),
        ));

        $event_id = userlogLogger::log(array(
            'app_id'       => 'shop',
            'action'       => 'category.delete',
            'entity_type'  => 'category',
            'entity_id'    => $category_id,
            'entity_name'  => $category['name'],
            'summary'      => 'Удалена категория «'.$category['name'].'»',
            'before_data'  => $snapshot,
            'can_rollback' => 1,
            'trash_id'     => $trash_id,
        ));

        if ($event_id) {
            $trash_model->updateById($trash_id, array('event_id' => $event_id));
        }

        return $trash_id;
    }

    public function restore($trash_id)
    {
        if (!wa()->appExists('shop')) {
            throw new waException('Приложение «Магазин» недоступно');
        }
        wa('shop');

        $trash_model = new userlogTrashModel();
        $item = $trash_model->getById($trash_id);
        if (!$item || $item['restored_at']) {
            throw new waException('Запись не найдена или уже восстановлена');
        }

        $snapshot = waUtils::jsonDecode($item['snapshot'], true);
        if ($item['entity_type'] === 'product') {
            $new_id = shopUserlogProductSnapshot::restore($snapshot, $item['files_path'], (int) $item['original_id']);
        } elseif ($item['entity_type'] === 'category') {
            $new_id = shopUserlogCategorySnapshot::restore($snapshot, (int) $item['original_id']);
        } else {
            throw new waException('Неизвестный тип объекта');
        }

        $trash_model->updateById($trash_id, array(
            'restored_at' => date('Y-m-d H:i:s'),
            'restored_by' => wa()->getUser()->getId(),
        ));

        userlogLogger::log(array(
            'app_id'      => 'shop',
            'action'      => $item['entity_type'].'.create',
            'entity_type' => $item['entity_type'],
            'entity_id'   => $new_id,
            'entity_name' => $item['name'],
            'summary'     => 'Восстановлено из корзины: «'.$item['name'].'»',
            'after_data'  => array('restored_from_trash' => $trash_id),
            'can_rollback'=> 0,
        ));

        return $new_id;
    }

    public function purgeExpired()
    {
        $trash_model = new userlogTrashModel();
        $ids = $trash_model->getExpiredIds();
        foreach ($ids as $id) {
            $this->purgeOne($id);
        }
        return count($ids);
    }

    public function purgeOne($trash_id)
    {
        $trash_model = new userlogTrashModel();
        $item = $trash_model->getById($trash_id);
        if (!$item) {
            return false;
        }
        if ($item['files_path'] && file_exists($item['files_path'])) {
            try {
                waFiles::delete($item['files_path']);
            } catch (Exception $e) {
            }
        }
        return $trash_model->deleteById($trash_id);
    }
}
