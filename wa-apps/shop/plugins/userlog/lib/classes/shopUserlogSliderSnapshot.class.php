<?php

class shopUserlogSliderSnapshot
{
    public static function captureAll()
    {
        $slides = array();
        foreach ((new shopSliderModel())->order('sort ASC, id ASC')->fetchAll() as $slide) {
            $slides[] = self::normalizeSlide($slide);
        }

        return array(
            'slides'      => $slides,
            'captured_at' => date('Y-m-d H:i:s'),
        );
    }

    public static function normalizeSlide(array $slide)
    {
        return array(
            'id'               => (int) ifset($slide, 'id', 0),
            'sort'             => (int) ifset($slide, 'sort', 0),
            'link'             => trim((string) ifset($slide, 'link', '')),
            'alt'              => trim((string) ifset($slide, 'alt', '')),
            'enabled'          => (int) ifset($slide, 'enabled', 1),
            'date_from'        => (string) ifset($slide, 'date_from', ''),
            'date_to'          => (string) ifset($slide, 'date_to', ''),
            'sales_manager'    => trim((string) ifset($slide, 'sales_manager', '')),
            'content_manager'  => trim((string) ifset($slide, 'content_manager', '')),
            'img'              => basename((string) ifset($slide, 'img', '')),
            'img_tablet'       => basename((string) ifset($slide, 'img_tablet', '')),
            'img_mobile'       => basename((string) ifset($slide, 'img_mobile', '')),
        );
    }

    public static function flattenForDiff(array $snapshot)
    {
        $flat = array();
        foreach (ifset($snapshot, 'slides', array()) as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $id = (int) ifset($slide, 'id', 0);
            $prefix = $id ? 'Слайд #'.$id : 'Слайд';
            foreach (self::fieldLabels() as $field => $label) {
                $flat[$prefix.' — '.$label] = self::formatFieldValue($field, ifset($slide, $field, ''));
            }
        }
        ksort($flat);
        return $flat;
    }

    protected static function fieldLabels()
    {
        return array(
            'sort'            => 'Сортировка',
            'link'            => 'Ссылка',
            'alt'             => 'Alt-текст',
            'enabled'         => 'Включён',
            'date_from'       => 'Дата с',
            'date_to'         => 'Дата по',
            'sales_manager'   => 'Менеджер продаж',
            'content_manager' => 'Контент-менеджер',
            'img'             => 'Изображение',
            'img_tablet'      => 'Планшет',
            'img_mobile'      => 'Мобильное',
        );
    }

    protected static function formatFieldValue($field, $value)
    {
        if ($field === 'enabled') {
            return (int) $value ? 'да' : 'нет';
        }
        return (string) $value;
    }
}
