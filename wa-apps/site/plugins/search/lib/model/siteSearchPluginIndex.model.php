<?php

class siteSearchPluginIndexModel extends waModel
{
    protected $table = 'site_search_plugin_index';

    public function getPages($word_ids, $offset, $limit, $domain_id, $status = 1)
    {
        $parts = array();
        $word_ids[] = 0;
        $parts['where'] = 'si.word_id IN ('.implode(",", $word_ids).')';

        if (count($word_ids) > 1) {
            $parts['fields'] = array('SUM(si.weight) AS weight');
            $parts['order_by'] = 'weight DESC';
            $parts['group_by'] = 'p.id';
        } else {
            $parts['fields'] = array("si.weight");
            $parts['order_by'] = 'si.weight DESC';
            $parts['group_by'] = false;
        }
        $parts['fields'][] = 'p.*';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS '.implode(', ', $parts['fields']). ' FROM site_page p '.
            ' JOIN site_search_plugin_index si ON si.page_id = p.id '.
            ' WHERE p.domain_id = i? AND p.status = i? AND '. $parts['where']. ' ';
        if($parts['group_by']) {
            $sql .= ' GROUP BY '.$parts['group_by']. ' ';
        }
        $sql .= ' ORDER BY '.$parts['order_by'];
        $sql .= ' LIMIT i?, i?';


        $pages = $this->query($sql, $domain_id, $status, $offset, $limit)->fetchAll();
        $total = $this->query('SELECT FOUND_ROWS()')->fetchArray();

        return array(
            'pages' => $pages,
            'total' => $total[0]
        );
    }

    public function truncate()
    {
        $this->query("TRUNCATE TABLE ".$this->table);
    }
}