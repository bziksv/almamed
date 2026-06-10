<?php

class siteSearchPluginWordModel extends waModel
{
    protected $table = 'site_search_plugin_word';
    protected static $words_cache = array();

    /**
     * @param string $word
     * @return int
     */
    public function getId($word)
    {
        if (isset(self::$words_cache[$word])) {
            return self::$words_cache[$word];
        }
        if (mb_strlen($word) > 255) {
            $word = mb_strlen($word, 0, 255);
        }
        $row = $this->select('*')->where('name LIKE ?', $word)->fetch();
        if ($row) {
            $id = $row['id'];
        } else {
            $id = $this->insert(array('name' => $word), 2);
        }
        if (count(self::$words_cache) > 5000) {
            self::$words_cache = array();
        }
        if ($id) {
            self::$words_cache[$word] = $id;
        }
        return $id;
    }

    public function getByString($string)
    {
        $words = preg_split("/[\s,]+/u", $string);
        $where = array();
        foreach ($words as $w) {
            $w = trim($w);
            if ($w) {
                $where[] = "name LIKE '".$this->escape(siteSearchPluginIndex::stem($w), 'like')."'";
            }

        }
        if ($where) {
            $sql = "SELECT id FROM ".$this->table." WHERE ".implode(' OR ', $where);
            return $this->query($sql)->fetchAll(null, true);
        }
        return array();
    }

}