<?php

class shopLeadsPluginReportExportController extends waController
{
    public function execute()
    {
        $u = wa()->getUser();
        if (!$u->isAdmin('shop') && !$u->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }

        $filters = array(
            'source'          => waRequest::get('source', '', waRequest::TYPE_STRING_TRIM),
            'status'          => waRequest::get('status', '', waRequest::TYPE_STRING_TRIM),
            'date_from'       => waRequest::get('date_from', '', waRequest::TYPE_STRING_TRIM),
            'date_to'         => waRequest::get('date_to', '', waRequest::TYPE_STRING_TRIM),
            'q'               => waRequest::get('q', '', waRequest::TYPE_STRING_TRIM),
            'hide_duplicates' => waRequest::get('hide_duplicates', 0, waRequest::TYPE_INT),
        );

        $model = new shopLeadsPluginLeadModel();
        $rows = $model->getFiltered($filters, 0, 5000);
        $sources = shopLeadsPlugin::sourceLabels();
        $statuses = shopLeadsPlugin::statusLabels();

        $filename = 'leads-' . date('Y-m-d-His') . '.csv';
        $response = wa()->getResponse();
        $response->addHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->sendHeaders();

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, array(
            'ID',
            'Дата',
            'Источник',
            'Статус',
            'ФИО',
            'Телефон',
            'E-mail',
            'Город',
            'Клиника',
            'ИНН',
            'Комментарий',
            'Товар',
            'URL товара',
            'Страница',
            'Roistat',
            'Почта OK',
            'Дубль от',
            'IP',
        ), ';');

        foreach ($rows as $r) {
            fputcsv($out, array(
                $r['id'],
                $r['created_at'],
                ifset($sources, $r['source'], $r['source']),
                ifset($statuses, $r['status'], $r['status']),
                $r['name'],
                $r['phone'],
                $r['email'],
                $r['city'],
                $r['clinic'],
                $r['clinic_inn'],
                $r['comment'],
                $r['product_name'],
                $r['product_url'],
                $r['page_send'],
                $r['roistat'],
                $r['mail_ok'] ? 'да' : 'нет',
                $r['duplicate_of'] ? $r['duplicate_of'] : '',
                $r['ip'],
            ), ';');
        }

        fclose($out);
        exit;
    }
}
