<?php

class userlogEventModel extends waModel
{
    protected $table = 'userlog_event';

    public function log(array $data)
    {
        if (empty($data['datetime'])) {
            $data['datetime'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['contact_id'])) {
            $data['contact_id'] = wa()->getUser()->getId();
        }
        if (!isset($data['ip'])) {
            $data['ip'] = waRequest::getIp();
        }
        if (!isset($data['user_agent'])) {
            $ua = waRequest::server('HTTP_USER_AGENT');
            if ($ua) {
                $data['user_agent'] = mb_substr($ua, 0, 255);
            }
        }
        foreach (array('before_data', 'after_data') as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = waUtils::jsonEncode($data[$field]);
            }
        }
        return $this->insert($data);
    }

    public function getEvents(array $options = array())
    {
        $limit = max(1, min(200, (int) ifset($options, 'limit', 50)));
        $offset = max(0, (int) ifset($options, 'offset', 0));

        $where = array('1=1');
        $bind = array();

        if (!empty($options['contact_id'])) {
            $where[] = 'e.contact_id = i:contact_id';
            $bind['contact_id'] = (int) $options['contact_id'];
        }
        if (!empty($options['action'])) {
            $where[] = 'e.action = s:action';
            $bind['action'] = $options['action'];
        }
        if (!empty($options['hide_auth'])) {
            $where[] = "e.action NOT IN ('auth.login', 'auth.logout', 'auth.login_failed')";
        }
        if (!empty($options['entity_type'])) {
            $where[] = 'e.entity_type = s:entity_type';
            $bind['entity_type'] = $options['entity_type'];
        }
        if (!empty($options['entity_id'])) {
            $where[] = 'e.entity_id = i:entity_id';
            $bind['entity_id'] = (int) $options['entity_id'];
        }
        if (!empty($options['query'])) {
            $q = $options['query'];
            if (preg_match('/^\d+$/', $q)) {
                $where[] = '(e.entity_id = i:entity_id_q OR e.summary LIKE s:query OR e.entity_name LIKE s:query)';
                $bind['entity_id_q'] = (int) $q;
            } else {
                $where[] = '(e.summary LIKE s:query OR e.entity_name LIKE s:query)';
            }
            $bind['query'] = '%'.$q.'%';
        }
        if (!empty($options['date_from'])) {
            $where[] = 'e.datetime >= s:date_from';
            $bind['date_from'] = $options['date_from'].' 00:00:00';
        }
        if (!empty($options['date_to'])) {
            $where[] = 'e.datetime <= s:date_to';
            $bind['date_to'] = $options['date_to'].' 23:59:59';
        }

        $sql = "SELECT e.*, c.name AS contact_name, c.photo AS contact_photo,
                       rb.name AS rolled_back_by_name
                FROM {$this->table} e
                LEFT JOIN wa_contact c ON c.id = e.contact_id
                LEFT JOIN wa_contact rb ON rb.id = e.rolled_back_by
                WHERE ".implode(' AND ', $where)."
                  AND NOT EXISTS (
                    SELECT 1 FROM {$this->table} dup
                    WHERE dup.entity_id = e.entity_id
                      AND dup.action = e.action
                      AND dup.datetime = e.datetime
                      AND dup.id != e.id
                      AND dup.before_data IS NOT NULL
                      AND dup.before_data != ''
                      AND (e.before_data IS NULL OR e.before_data = '')
                      AND e.can_rollback = 0
                  )
                ORDER BY e.datetime DESC, e.id DESC
                LIMIT {$offset}, {$limit}";

        return $this->query($sql, $bind)->fetchAll();
    }

    public function countEvents(array $options = array())
    {
        $where = array('1=1');
        $bind = array();

        if (!empty($options['contact_id'])) {
            $where[] = 'contact_id = i:contact_id';
            $bind['contact_id'] = (int) $options['contact_id'];
        }
        if (!empty($options['action'])) {
            $where[] = 'action = s:action';
            $bind['action'] = $options['action'];
        }
        if (!empty($options['hide_auth'])) {
            $where[] = "action NOT IN ('auth.login', 'auth.logout', 'auth.login_failed')";
        }
        if (!empty($options['query'])) {
            $q = $options['query'];
            if (preg_match('/^\d+$/', $q)) {
                $where[] = '(entity_id = i:entity_id_q OR summary LIKE s:query OR entity_name LIKE s:query)';
                $bind['entity_id_q'] = (int) $q;
            } else {
                $where[] = '(summary LIKE s:query OR entity_name LIKE s:query)';
            }
            $bind['query'] = '%'.$q.'%';
        }

        if (!empty($options['entity_type'])) {
            $where[] = 'entity_type = s:entity_type';
            $bind['entity_type'] = $options['entity_type'];
        }
        if (!empty($options['date_from'])) {
            $where[] = 'datetime >= s:date_from';
            $bind['date_from'] = $options['date_from'].' 00:00:00';
        }
        if (!empty($options['date_to'])) {
            $where[] = 'datetime <= s:date_to';
            $bind['date_to'] = $options['date_to'].' 23:59:59';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE ".implode(' AND ', $where);
        return (int) $this->query($sql, $bind)->fetchField();
    }

    public function getBackendUsers($include_contact_id = 0)
    {
        $sql = "SELECT c.id AS contact_id, c.name AS contact_name
                FROM wa_contact c
                WHERE c.login IS NOT NULL AND c.is_user = 1
                ORDER BY c.name";
        $users = $this->query($sql)->fetchAll('contact_id');

        $include_contact_id = (int) $include_contact_id;
        if ($include_contact_id && empty($users[$include_contact_id])) {
            $contact = new waContact($include_contact_id);
            if ($contact->exists()) {
                $users[$include_contact_id] = array(
                    'contact_id'   => $include_contact_id,
                    'contact_name' => $contact->getName(),
                );
            }
        }

        return $users;
    }

    /** @deprecated use getBackendUsers() */
    public function getDistinctUsers()
    {
        return $this->getBackendUsers();
    }

    public function existsByWaLogId($wa_log_id)
    {
        $wa_log_id = (int) $wa_log_id;
        if (!$wa_log_id) {
            return false;
        }
        $sql = "SELECT id FROM {$this->table}
                WHERE after_data LIKE s:needle
                LIMIT 1";
        return (bool) $this->query($sql, array(
            'needle' => '%"_wa_log_id":'.$wa_log_id.'%',
        ))->fetchField();
    }

    /**
     * Same action on same object at the same second — prefer entry with before/after snapshot.
     */
    public function findRicherDuplicate(array $event)
    {
        if (empty($event['entity_id']) || empty($event['datetime']) || empty($event['action'])) {
            return null;
        }
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE entity_id = i:entity_id
               AND action = s:action
               AND datetime = s:datetime
               AND id != i:id
               AND before_data IS NOT NULL
               AND before_data != ''
             ORDER BY can_rollback DESC, id DESC
             LIMIT 1",
            array(
                'entity_id' => (int) $event['entity_id'],
                'action'    => $event['action'],
                'datetime'  => $event['datetime'],
                'id'        => (int) $event['id'],
            )
        )->fetch();
    }

    /**
     * Previous journal entry for the same object (e.g. infer title change from wa_log imports).
     */
    public function findPreviousEntityEvent($entity_id, $app_id, $before_datetime, $exclude_id = 0)
    {
        $entity_id = (int) $entity_id;
        if (!$entity_id || !$before_datetime) {
            return null;
        }
        return $this->query(
            "SELECT * FROM {$this->table}
             WHERE entity_id = i:entity_id
               AND app_id = s:app_id
               AND datetime < s:datetime
               AND id != i:exclude_id
             ORDER BY datetime DESC, id DESC
             LIMIT 1",
            array(
                'entity_id' => $entity_id,
                'app_id'    => $app_id,
                'datetime'  => $before_datetime,
                'exclude_id'=> (int) $exclude_id,
            )
        )->fetch();
    }

    public function purgeThinDuplicates()
    {
        return $this->exec(
            "DELETE e FROM {$this->table} e
             INNER JOIN {$this->table} rich ON rich.entity_id = e.entity_id
               AND rich.action = e.action
               AND rich.datetime = e.datetime
               AND rich.id != e.id
               AND rich.before_data IS NOT NULL
               AND rich.before_data != ''
             WHERE (e.before_data IS NULL OR e.before_data = '')
               AND e.can_rollback = 0
               AND e.action IN ('product.update', 'category.update', 'post.update')"
        );
    }

    public function getActionLabels()
    {
        return userlogHelper::getActionLabels();
    }
}
