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

            $slide['sales_manager_label'] = self::resolveContactLabel(
                ifset($slide, 'sales_manager_id', 0),
                ifset($slide, 'sales_manager', '')
            );
            $slide['content_manager_label'] = self::resolveContactLabel(
                ifset($slide, 'content_manager_id', 0),
                ifset($slide, 'content_manager', '')
            );
        }
        unset($slide);

        $this->view->assign('forms', $records);
        $this->view->assign('active_count', $active_count);
        $this->view->assign('max_visible_slides', shopSliderPlugin::MAX_VISIBLE_SLIDES);
        $this->view->assign('slider_sizes', shopSliderResponsiveImages::adminSizeHints());
        $this->view->assign(
            'contact_autocomplete_url',
            wa()->getAppUrl('shop') . '?action=autocomplete&type=contact'
        );
        $this->view->assign(
            'contact_autocomplete_url_json',
            json_encode(wa()->getAppUrl('shop') . '?action=autocomplete&type=contact')
        );
    }

    protected static function resolveContactLabel($contact_id, $fallback = '')
    {
        $contact_id = (int) $contact_id;
        if ($contact_id) {
            $contact = new waContact($contact_id);
            if ($contact->exists()) {
                return $contact->getName();
            }
        }

        return (string) $fallback;
    }
}
