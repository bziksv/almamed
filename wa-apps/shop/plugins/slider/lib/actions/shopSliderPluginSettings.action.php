<?php

class shopSliderPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $model = new shopSliderModel();

        $records = $model->order('sort ASC')->fetchAll();
        $active_count = 0;

        foreach ($records as &$slide) {
            $slide['is_visible'] = shopSliderPlugin::isSlideVisible($slide);
            if ($slide['is_visible']) {
                $active_count++;
            }

            $views = (int) ifset($slide, 'views_count', 0);
            $clicks = (int) ifset($slide, 'clicks_count', 0);
            $slide['ctr'] = $views > 0 ? round(100 * $clicks / $views, 1) : null;
        }
        unset($slide);

        $this->view->assign('forms', $records);
        $this->view->assign('active_count', $active_count);
        $this->view->assign('max_visible_slides', shopSliderPlugin::MAX_VISIBLE_SLIDES);
    }
}
