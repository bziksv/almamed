<?php

class shopSliderPluginBackendSaveController extends waJsonController
{
    public function execute()
    {
        $model = new shopSliderModel();

        $sort = waRequest::post('sort');
        $link = waRequest::post('link');
        $alt = waRequest::post('alt');

        $img = $this->processImageField('img', waRequest::post('img_path'));
        $img_tablet = $this->processImageField('img_tablet', waRequest::post('img_tablet_path'));
        $img_mobile = $this->processImageField('img_mobile', waRequest::post('img_mobile_path'));

        $records = $model->order('sort ASC')->fetchAll();
        foreach ($records as $r) {
            $model->deleteById($r['id']);
        }

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

            $model->insert(array(
                'sort' => ifset($sort, $key, 0),
                'link' => ifset($link, $key, ''),
                'alt' => ifset($alt, $key, ''),
                'img' => $db,
                'img_tablet' => $tablet,
                'img_mobile' => $mobile,
            ));
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

            $image = waImage::factory($_FILES[$field]['tmp_name'][$key]);
            $image->save($files_root, 100);

            if ($field === 'img') {
                shopSliderResponsiveImages::generateFromDesktop($public_path, true);
            }

            $files[$key] = $public_path;
        }

        return $files;
    }
}
