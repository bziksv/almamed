<?php

class shopSliderPluginBackendSaveController extends waJsonController
{
    public function execute()
    {
        $model = new shopSliderModel();

        $ids = waRequest::post('id');
        $sort = waRequest::post('sort');
        $link = waRequest::post('link');
        $alt = waRequest::post('alt');
        $enabled = waRequest::post('enabled');
        $date_from = waRequest::post('date_from');
        $date_to = waRequest::post('date_to');
        $sales_manager = waRequest::post('sales_manager');
        $content_manager = waRequest::post('content_manager');
        $sales_manager_id = waRequest::post('sales_manager_id');
        $content_manager_id = waRequest::post('content_manager_id');

        $img = $this->processImageField('img', waRequest::post('img_path'));
        $img_tablet = $this->processImageField('img_tablet', waRequest::post('img_tablet_path'));
        $img_mobile = $this->processImageField('img_mobile', waRequest::post('img_mobile_path'));

        $existing = array();
        foreach ($model->order('sort ASC')->fetchAll() as $record) {
            $existing[$record['id']] = $record;
        }

        $table_fields = $this->getTableFields($model);
        $kept_ids = array();

        foreach ($img as $key => $db) {
            if (!$db) {
                continue;
            }
            $tablet = ifset($img_tablet, $key, '');
            $mobile = ifset($img_mobile, $key, '');

            if ($db) {
                $generated = shopSliderResponsiveImages::generateFromDesktop($db, false);
                if (!$tablet) {
                    $tablet = $generated['img_tablet'];
                }
                if (!$mobile) {
                    $mobile = $generated['img_mobile'];
                }
            }

            $sales = self::resolveManagerField(
                ifset($sales_manager_id, $key, 0),
                ifset($sales_manager, $key, '')
            );
            $content = self::resolveManagerField(
                ifset($content_manager_id, $key, 0),
                ifset($content_manager, $key, '')
            );

            $slide_id = (int) ifset($ids, $key, 0);
            $data = array(
                'sort' => ifset($sort, $key, 0),
                'link' => ifset($link, $key, ''),
                'alt' => ifset($alt, $key, ''),
                'img' => $db,
                'img_tablet' => $tablet,
                'img_mobile' => $mobile,
                'enabled' => (int) ifset($enabled, $key, 1),
                'date_from' => self::normalizeDate(ifset($date_from, $key, '')),
                'date_to' => self::normalizeDate(ifset($date_to, $key, '')),
                'sales_manager' => $sales['name'],
                'sales_manager_id' => $sales['id'],
                'content_manager' => $content['name'],
                'content_manager_id' => $content['id'],
            );

            if ($slide_id && isset($existing[$slide_id])) {
                $data['views_count'] = (int) ifset($existing[$slide_id], 'views_count', 0);
                $data['clicks_count'] = (int) ifset($existing[$slide_id], 'clicks_count', 0);
                $model->updateById($slide_id, $this->filterDataByTableFields($data, $table_fields));
                $kept_ids[] = $slide_id;
                continue;
            }

            $data['views_count'] = 0;
            $data['clicks_count'] = 0;
            $kept_ids[] = $model->insert($this->filterDataByTableFields($data, $table_fields));
        }

        foreach ($existing as $id => $record) {
            if (!in_array($id, $kept_ids, true)) {
                $model->deleteById($id);
            }
        }

        $this->redirect('/webasyst/shop/?action=plugins#/slider/');
    }

    protected function processImageField($field, $paths)
    {
        $files = array();

        if (!isset($_FILES[$field]['error']) || !is_array($_FILES[$field]['error'])) {
            if (is_array($paths)) {
                foreach ($paths as $key => $path) {
                    $files[$key] = $path;
                }
            }
            return $files;
        }

        foreach ($_FILES[$field]['error'] as $key => $error) {
            if ($error) {
                $files[$key] = ifset($paths, $key, '');
                continue;
            }

            $format = array('/png', '/jpeg');
            if (!in_array(strstr($_FILES[$field]['type'][$key], '/'), $format)) {
                $files[$key] = false;
                continue;
            }

            $file_name = $_FILES[$field]['name'][$key];
            $public_path = '/wa-data/public/shop/slider/img/' . $file_name;
            $files_root = wa()->getConfig()->getPath('data') . '/public/shop/slider/img/' . $file_name;

            waFiles::create(wa()->getConfig()->getPath('data') . '/public/shop/slider/img');

            if (class_exists('shopSliderImageOptimizer')) {
                shopSliderImageOptimizer::saveUploaded(
                    $_FILES[$field]['tmp_name'][$key],
                    $files_root,
                    $field
                );
            } else {
                $image = waImage::factory($_FILES[$field]['tmp_name'][$key]);
                $image->save($files_root, 85);
            }

            if ($field === 'img') {
                shopSliderResponsiveImages::generateFromDesktop($public_path, true);
            }

            $files[$key] = $public_path;
        }

        return $files;
    }

    protected static function normalizeDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $date = date_create_from_format('Y-m-d', $value);
        if (!$date) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    protected static function resolveManagerField($contact_id, $fallback_name)
    {
        $contact_id = (int) $contact_id;
        $fallback_name = trim((string) $fallback_name);

        if ($contact_id) {
            $contact = new waContact($contact_id);
            if ($contact->exists()) {
                return array(
                    'id' => $contact_id,
                    'name' => $contact->getName(),
                );
            }
        }

        return array(
            'id' => null,
            'name' => $fallback_name,
        );
    }

    protected function getTableFields(shopSliderModel $model)
    {
        static $fields = null;
        if ($fields !== null) {
            return $fields;
        }

        $fields = array();
        foreach ($model->query('SHOW COLUMNS FROM shop_slider')->fetchAll() as $row) {
            $fields[$row['Field']] = true;
        }

        return $fields;
    }

    protected function filterDataByTableFields(array $data, array $fields)
    {
        return array_intersect_key($data, $fields);
    }
}
