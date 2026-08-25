<?php

class shopLeadsPluginLeadModel extends waModel
{
    protected $table = 'shop_leads_plugin_lead';

    const DUPLICATE_WINDOW_MINUTES = 10;

    /**
     * @param array $data
     * @param int|null $duplicate_minutes null = use model default
     * @return int
     */
    public function addLead(array $data, $duplicate_minutes = null)
    {
        $row = array(
            'created_at'      => ifset($data, 'created_at', date('Y-m-d H:i:s')),
            'source'          => (string) ifset($data, 'source', ''),
            'status'          => (string) ifset($data, 'status', shopLeadsPlugin::STATUS_NEW),
            'name'            => mb_substr(trim((string) ifset($data, 'name', '')), 0, 255),
            'phone'           => mb_substr(trim((string) ifset($data, 'phone', '')), 0, 64),
            'email'           => mb_substr(trim((string) ifset($data, 'email', '')), 0, 255),
            'city'            => mb_substr(trim((string) ifset($data, 'city', '')), 0, 255),
            'clinic'          => mb_substr(trim((string) ifset($data, 'clinic', '')), 0, 255),
            'clinic_inn'      => mb_substr(trim((string) ifset($data, 'clinic_inn', '')), 0, 32),
            'comment'         => (string) ifset($data, 'comment', ''),
            'product_id'      => !empty($data['product_id']) ? (int) $data['product_id'] : null,
            'product_name'    => mb_substr(trim((string) ifset($data, 'product_name', '')), 0, 512),
            'product_url'     => mb_substr(trim((string) ifset($data, 'product_url', '')), 0, 512),
            'page_send'       => mb_substr(trim((string) ifset($data, 'page_send', '')), 0, 512),
            'roistat'         => mb_substr(trim((string) ifset($data, 'roistat', '')), 0, 64),
            'ip'              => mb_substr(trim((string) ifset($data, 'ip', waRequest::getIp())), 0, 45),
            'user_agent'      => mb_substr(trim((string) ifset($data, 'user_agent', (string) waRequest::getUserAgent())), 0, 512),
            'mail_ok'         => !empty($data['mail_ok']) ? 1 : 0,
            'mail_error'      => isset($data['mail_error']) ? mb_substr((string) $data['mail_error'], 0, 255) : null,
            'payload'         => array_key_exists('payload', $data) && $data['payload'] !== null
                ? (is_string($data['payload']) ? $data['payload'] : json_encode($data['payload'], JSON_UNESCAPED_UNICODE))
                : null,
            'attachment_name' => isset($data['attachment_name'])
                ? mb_substr((string) $data['attachment_name'], 0, 255)
                : null,
            'duplicate_of'    => null,
        );

        $window = $duplicate_minutes === null ? self::DUPLICATE_WINDOW_MINUTES : (int) $duplicate_minutes;
        if ($window > 0) {
            $dup_id = $this->findRecentDuplicate($row, $window);
            if ($dup_id) {
                $row['duplicate_of'] = $dup_id;
                $row['status'] = shopLeadsPlugin::STATUS_SPAM;
            }
        }

        return (int) $this->insert($row);
    }

    /**
     * Same source + phone digits (+ product for KP) within N minutes.
     *
     * @param array $row
     * @param int $minutes
     * @return int|null
     */
    public function findRecentDuplicate(array $row, $minutes = null)
    {
        $phone_digits = preg_replace('/\D+/', '', ifset($row, 'phone', ''));
        if (strlen($phone_digits) < 10) {
            return null;
        }

        $minutes = $minutes === null ? self::DUPLICATE_WINDOW_MINUTES : (int) $minutes;
        if ($minutes <= 0) {
            return null;
        }

        $since = date('Y-m-d H:i:s', time() - $minutes * 60);
        $source = $this->escape(ifset($row, 'source', ''));

        $sql = 'SELECT id, phone, product_id FROM ' . $this->table
            . ' WHERE source = \'' . $source . '\''
            . ' AND created_at >= \'' . $this->escape($since) . '\''
            . ' AND (duplicate_of IS NULL OR duplicate_of = 0)'
            . ' ORDER BY id DESC LIMIT 30';

        $candidates = $this->query($sql)->fetchAll();
        $product_id = !empty($row['product_id']) ? (int) $row['product_id'] : 0;
        $source_raw = ifset($row, 'source', '');

        foreach ($candidates as $c) {
            $c_digits = preg_replace('/\D+/', '', $c['phone']);
            if ($c_digits !== $phone_digits) {
                continue;
            }
            if ($source_raw === shopLeadsPlugin::SOURCE_KP) {
                $c_pid = (int) $c['product_id'];
                if ($product_id && $c_pid && $product_id !== $c_pid) {
                    continue;
                }
            }
            return (int) $c['id'];
        }

        return null;
    }

    /**
     * @param array $filters
     * @return string WHERE clause without leading WHERE
     */
    public function buildWhere(array $filters)
    {
        $where = array('1=1');

        if (!empty($filters['source'])) {
            $where[] = 'source = \'' . $this->escape($filters['source']) . '\'';
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = \'' . $this->escape($filters['status']) . '\'';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= \'' . $this->escape($filters['date_from']) . ' 00:00:00\'';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= \'' . $this->escape($filters['date_to']) . ' 23:59:59\'';
        }
        if (!empty($filters['q'])) {
            $q = $this->escape($filters['q'], 'like');
            $where[] = '(name LIKE \'%' . $q . '%\' OR phone LIKE \'%' . $q . '%\' OR email LIKE \'%' . $q . '%\' OR clinic LIKE \'%' . $q . '%\' OR product_name LIKE \'%' . $q . '%\')';
        }
        if (!empty($filters['hide_duplicates'])) {
            $where[] = '(duplicate_of IS NULL OR duplicate_of = 0)';
        }

        return implode(' AND ', $where);
    }

    /**
     * @param array $filters
     * @return int
     */
    public function countFiltered(array $filters)
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table . ' WHERE ' . $this->buildWhere($filters);
        return (int) $this->query($sql)->fetchField();
    }

    /**
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getFiltered(array $filters, $offset = 0, $limit = 50)
    {
        $offset = max(0, (int) $offset);
        $limit = max(1, min(5000, (int) $limit));
        $sql = 'SELECT * FROM ' . $this->table
            . ' WHERE ' . $this->buildWhere($filters)
            . ' ORDER BY created_at DESC, id DESC'
            . ' LIMIT ' . $offset . ', ' . $limit;
        return $this->query($sql)->fetchAll();
    }

    /**
     * @return int
     */
    public function countNew()
    {
        return (int) $this->countByField('status', shopLeadsPlugin::STATUS_NEW);
    }

    /**
     * @param array $ids
     * @param string $status
     * @return int affected
     */
    public function updateStatusByIds(array $ids, $status)
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) {
            return 0;
        }
        $allowed = array_keys(shopLeadsPlugin::statusLabels());
        if (!in_array($status, $allowed, true)) {
            return 0;
        }
        return $this->updateById($ids, array('status' => $status));
    }

    /**
     * Delete leads older than N months.
     *
     * @param int $months
     * @return int deleted count
     */
    public function purgeOlderThanMonths($months)
    {
        $months = (int) $months;
        if ($months <= 0) {
            return 0;
        }
        $before = date('Y-m-d H:i:s', strtotime('-' . $months . ' months'));
        $sql = 'SELECT COUNT(*) FROM ' . $this->table
            . ' WHERE created_at < \'' . $this->escape($before) . '\'';
        $count = (int) $this->query($sql)->fetchField();
        if ($count > 0) {
            $this->exec(
                'DELETE FROM ' . $this->table
                . ' WHERE created_at < \'' . $this->escape($before) . '\''
            );
        }
        return $count;
    }
}
