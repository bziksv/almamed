<?php

class shopAbdisplayPluginEventModel extends waModel
{
    protected $table = 'shop_abdisplay_event';

    /**
     * @param array $row
     * @return int|null
     */
    public function addEvent(array $row)
    {
        $data = array(
            'experiment_key'  => (string) ifset($row, 'experiment_key', ''),
            'abtest_id'       => (int) ifset($row, 'abtest_id', 0),
            'variant_id'      => (int) ifset($row, 'variant_id', 0),
            'variant_code'    => (string) ifset($row, 'variant_code', ''),
            'event'           => (string) ifset($row, 'event', ''),
            'url'             => mb_substr((string) ifset($row, 'url', ''), 0, 512),
            'contact_id'      => (int) ifset($row, 'contact_id', 0),
            'session_id'      => mb_substr((string) ifset($row, 'session_id', ''), 0, 64),
            'create_datetime' => date('Y-m-d H:i:s'),
            'meta'            => null,
        );
        if (isset($row['meta'])) {
            if (is_array($row['meta'])) {
                $data['meta'] = json_encode($row['meta'], JSON_UNESCAPED_UNICODE);
            } else {
                $data['meta'] = (string) $row['meta'];
            }
        }
        if ($data['experiment_key'] === '' || $data['event'] === '') {
            return null;
        }
        return (int) $this->insert($data);
    }

    /**
     * Counts by experiment + variant + event since datetime.
     *
     * @param string $experiment_key
     * @param string|null $since Y-m-d H:i:s
     * @return array [variant_code => [event => count]]
     */
    public function summarize($experiment_key, $since = null)
    {
        $sql = "SELECT variant_code, event, COUNT(*) AS cnt
                FROM {$this->table}
                WHERE experiment_key = s:key";
        $bind = array('key' => $experiment_key);
        if ($since) {
            $sql .= " AND create_datetime >= s:since";
            $bind['since'] = $since;
        }
        $sql .= " GROUP BY variant_code, event";
        $out = array();
        foreach ($this->query($sql, $bind) as $row) {
            $code = $row['variant_code'] !== '' ? $row['variant_code'] : '?';
            if (!isset($out[$code])) {
                $out[$code] = array();
            }
            $out[$code][$row['event']] = (int) $row['cnt'];
        }
        return $out;
    }
}
