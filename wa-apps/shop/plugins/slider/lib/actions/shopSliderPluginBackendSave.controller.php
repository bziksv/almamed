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

        $img = $this->processImageField('img', waRequest::post('img_path'));
        $img_tablet = $this->processImageField('img_tablet', waRequest::post('img_tablet_path'));
        $img_mobile = $this->processImageField('img_mobile', waRequest::post('img_mobile_path'));

        $existing = array();
        foreach ($model->fetchAll() as $record) {
            $existing[$record['id']] = $record;
        }

        $kept_ids = array();

        foreach ($img as $key => $db) {
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
                'sales_manager' => trim((string) ifset($sales_manager, $key, '')),
                'content_manager' => trim((string) ifset($content_manager, $key, '')),
            );

            if ($slide_id && isset($existing[$slide_id])) {
                $data['views_count'] = (int) ifset($existing[$slide_id], 'views_count', 0);
                $data['clicks_count'] = (int) ifset($existing[$slide_id], 'clicks_count', 0);
                $model->updateById($slide_id, $data);
                $kept_ids[] = $slide_id;
                continue;
            }

            $data['views_count'] = 0;
            $data['clicks_count'] = 0;
            $kept_ids[] = $model->insert($data);
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

            shopSliderImageOptimizer::saveUploaded(
                $_FILES[$field]['tmp_name'][$key],
                $files_root,
                $field
            );

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
}
