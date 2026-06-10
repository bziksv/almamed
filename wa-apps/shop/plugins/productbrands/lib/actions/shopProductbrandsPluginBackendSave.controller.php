<?php

class shopProductbrandsPluginBackendSaveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::get('id');
        $data = waRequest::post();

        $brands_model = new shopProductbrandsModel();
        $brand = $brands_model->getById($id);

        $feature_model = new shopFeatureValuesVarcharModel();
        $f = $feature_model->getById($id);

        $data['last_name_on'] = empty($data['last_name_on']) ? 0 : 1;
		
        $data['hidden'] = empty($data['hidden']) ? 0 : 1;

        if (count($data) > 1) {
            // change name of the brand, save new value for feature
            if ($data['name'] != $f['value']) {
                $feature_model->updateById($id, array('value' => $data['name']));
            }

            if (empty($data['image']) && $brand && $brand['image']) {
                waFiles::delete(wa()->getDataPath('brands/' . $id, true, 'shop', false));
            }

            if (waRequest::post('allow_filter')) {
                $data['filter'] = implode(',', ifset($data['filter'], array()));
            } else {
                $data['filter'] = null;
            }

            $data['enable_sorting'] = waRequest::post('enable_sorting') ? 1 : 0;
        } elseif (!$brand) {
            $data['name'] = $f['value'];
        }

        $image = waRequest::file('image_file');
        if ($image->uploaded()) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif', 'svg');
            $image_ext = strtolower($image->extension);
            if (in_array($image_ext, $allowed)) {
                if ($image_ext === 'svg' && function_exists('mime_content_type')) {
                    if (mime_content_type($image->tmp_name) !== 'image/svg+xml') {
                        $this->errors = 'Invalid svg file';
                        return;
                    }
                }
                $this->response['image'] = $data['image'] = '.' . $image_ext;
                $path = wa()->getDataPath('brands/' . $id . '/', true, 'shop');
                $image->moveTo($path, $id . $data['image']);
                $this->response['image_url'] = wa()->getDataUrl('brands/' . $id . '/' . $id . $data['image'], true, 'shop') . '?v' . time();
                if ($image_ext !== 'svg') {
                    $sizes = trim(wa()->getSetting('sizes', '', array('shop', 'productbrands')));
                    if ($sizes) {
                        $sizes = explode(';', $sizes);
                        foreach ($sizes as $size) {
                            if (!$size) {
                                continue;
                            }
                            if ($thumb_img = shopImage::generateThumb($path . $id . $data['image'], $size)) {
                                $thumb_img->save($path . $id . '.' . $size . $data['image']);
                            }
                        }
                    }
                }
            } else {
                $this->errors = sprintf(
                    _ws("Files with extensions %s are allowed only."),
                    '*.'.implode(', *.', $allowed)
                );
                return;
            }
        }

        if ($brand) {
            $brands_model->updateById($id, $data);
        } else {
            $data['id'] = $id;
            $brands_model->insert($data);
        }
    }

    public function display()
    {
        $this->getResponse()->sendHeaders();
        if (!$this->errors) {
            $data = array('status' => 'ok', 'data' => $this->response);
            echo json_encode($data);
        } else {
            echo json_encode(array('status' => 'fail', 'errors' => $this->errors));
        }
    }
}
