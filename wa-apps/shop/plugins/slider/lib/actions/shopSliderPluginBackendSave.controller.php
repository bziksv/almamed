<?php

class shopSliderPluginBackendSaveController extends waJsonController
{
    public function execute()
    {
        $model = new shopSliderModel();

        $sort = waRequest::post('sort');
        $link = waRequest::post('link');
        $alt = waRequest::post('alt');
        $img_path = waRequest::post('img_path');

        $files = array();
        foreach ($_FILES['img']['error'] as $key => $img) {
            if ($img) {
                $files[$key] = ifset($img_path, $key, '');
                if ($files[$key]) {
                    $this->ensureMobileThumbnail($files[$key]);
                }
            } else {
                $format = array('/png', '/jpeg');

                if (in_array(strstr($_FILES['img']['type'][$key], '/'), $format)) {
                    $file_name = $_FILES['img']['name'][$key];
                    $files[$key] = '/wa-data/public/shop/slider/img/' . $file_name;
                    $files_root = wa()->getConfig()->getPath('data') . '/public/shop/slider/img/' . $file_name;

                    waFiles::create(wa()->getConfig()->getPath('data') . '/public/shop/slider/img');

                    $image = waImage::factory($_FILES['img']['tmp_name'][$key]);
                    $image->save($files_root, 100);

                    $this->saveMobileThumbnail($_FILES['img']['tmp_name'][$key], $files_root);
                } else {
                    $files[$key] = false;
                }
            }
        }

        $records = $model->order('sort ASC')->fetchAll();
        foreach ($records as $r) {
            $model->deleteById($r['id']);
        }

        foreach ($files as $key => $db) {
            $model->insert(array(
                'sort' => $sort[$key],
                'link' => $link[$key],
                'alt' => $alt[$key],
                'img' => $db,
            ));
        }

        $this->redirect('/webasyst/shop/?action=plugins#/slider/');
    }

    protected function saveMobileThumbnail($source, $files_root)
    {
        $image_sm = waImage::factory($source);
        $image_sm->resize(576, 220, 'WIDTH');
        $image_sm->save($this->mobilePath($files_root), 100);
    }

    protected function ensureMobileThumbnail($public_path)
    {
        $basename = basename($public_path);
        $files_root = wa()->getConfig()->getPath('data') . '/public/shop/slider/img/' . $basename;
        if (!file_exists($files_root)) {
            return;
        }
        $mobile_path = $this->mobilePath($files_root);
        if (file_exists($mobile_path)) {
            return;
        }
        $this->saveMobileThumbnail($files_root, $files_root);
    }

    protected function mobilePath($files_root)
    {
        return str_replace('/img/', '/img/sm_', $files_root);
    }
}
