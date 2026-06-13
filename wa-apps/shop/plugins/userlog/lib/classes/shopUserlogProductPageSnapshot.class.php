<?php

class shopUserlogProductPageSnapshot
{
    public static function captureForLog($page_id)
    {
        $page = (new shopProductPagesModel())->getById($page_id);
        if (!$page) {
            return null;
        }
        return array('page' => $page);
    }

    public static function flattenForDiff(array $snapshot)
    {
        $page = ifset($snapshot, 'page', array());
        return array(
            'name'        => ifset($page, 'name', ''),
            'title'       => ifset($page, 'title', ''),
            'url'         => ifset($page, 'url', ''),
            'status'      => (int) ifset($page, 'status', 0) ? 'Опубликована' : 'Черновик',
            'content'     => userlogHelper::plainTextForDisplay(ifset($page, 'content', ''), 200),
            'keywords'    => ifset($page, 'keywords', ''),
            'description' => userlogHelper::plainTextForDisplay(ifset($page, 'description', ''), 120),
        );
    }
}
