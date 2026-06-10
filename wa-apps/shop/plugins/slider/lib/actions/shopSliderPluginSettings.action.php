<?php

class shopSliderPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        /**
         * @var shopProductbrandsPlugin $plugin
         */
        $model = new shopSliderModel();

        $records = $model->order('sort ASC')->fetchAll();
        $this->view->assign('forms', $records);
    }
}
