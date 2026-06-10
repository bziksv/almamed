<?php

class shopCustomfieldPlugin extends waPlugin
{
    public function backendCategoryDialog($category)
    {
        $model = new shopCustomfieldModel();
        $category['customfield_menu'] = $model->getByField('category_id', $category['id']);

        return waHtmlControl::getControl(waHtmlControl::INPUT, 'customfield_menu', array(
            'value' => ifset($category['customfield_menu']['name']),
            'title' => _wp('Название для меню'),
            'description' => _wp('Если поле заполнено, название в меню будет его значением.'),
            'title_wrapper' => '%s',
            'control_wrapper' => '<div class="field"><div class="name">%s</div><div class="value no-shift">%s%s</div></div>',
            'description_wrapper' => '<br><span class="hint">%s</span>',
        ));
    }

    public function categorySave($category)
    {
        $name = waRequest::post('customfield_menu', '', 'string');

        $model = new shopCustomfieldModel();
        $data = array(
            'category_id' => $category['id'],
            'name' => $name,
        );

        if(empty($data['name']))
            $model->deleteByField('category_id', $data['category_id']);
        else{
            if($model->countByField('category_id', $data['category_id']) == '0')
                $model->insert($data);
            else
                $model->updateByField('category_id', $data['category_id'], ['name' => $data['name']]);
        }
    }

    static public function get($id = 0){
        $model = new shopCustomfieldModel();
        $data = $model->getByField('category_id', $id);
        return ($data['name']) ?: false;
    }

}
