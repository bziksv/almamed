<?php

class userlogTrashModel extends waModel
{
    protected $table = 'userlog_trash';

    public function addItem(array $data)
    {
        if (empty($data['deleted_at'])) {
            $data['deleted_at'] = date('Y-m-d H:i:s');
        }
        if (empty($data['purge_at'])) {
            $days = userlogConfig::TRASH_RETENTION_DAYS;
            $data['purge_at'] = date('Y-m-d H:i:s', strtotime('+'.$days.' days'));
        }
        if (isset($data['snapshot']) && is_array($data['snapshot'])) {
            $data['snapshot'] = waUtils::jsonEncode($data['snapshot']);
        }
        return $this->insert($data);
    }

    public function getItems(array $options = array())
    {
        $limit = max(1, min(200, (int) ifset($options, 'limit', 50)));
        $offset = max(0, (int) ifset($options, 'offset', 0));

        $where = array('restored_at IS NULL');
        $bind = array();

        if (!empty($options['entity_type'])) {
            $where[] = 'entity_type = s:entity_type';
            $bind['entity_type'] = $options['entity_type'];
        }
        if (!empty($options['query'])) {
            $where[] = '(name LIKE s:query OR original_id = i:query_id)';
            $bind['query'] = '%'.$options['query'].'%';
            $bind['query_id'] = (int) $options['query'];
        }

        $sql = "SELECT t.*, c.name AS deleted_by_name
                FROM {$this->table} t
                LEFT JOIN wa_contact c ON c.id = t.deleted_by
                WHERE ".implode(' AND ', $where)."
                ORDER BY t.deleted_at DESC
                LIMIT {$offset}, {$limit}";

        return $this->query($sql, $bind)->fetchAll();
    }

    public function countActive(array $options = array())
    {
        $where = array('restored_at IS NULL');
        $bind = array();
        if (!empty($options['entity_type'])) {
            $where[] = 'entity_type = s:entity_type';
            $bind['entity_type'] = $options['entity_type'];
        }
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE ".implode(' AND ', $where);
        return (int) $this->query($sql, $bind)->fetchField();
    }

    public function getExpiredIds()
    {
        $sql = "SELECT id FROM {$this->table}
                WHERE restored_at IS NULL AND purge_at <= s:now";
        return $this->query($sql, array('now' => date('Y-m-d H:i:s')))->fetchAll(null, true);
    }
}
