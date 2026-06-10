<?php

class siteSearchPluginIndex {

    protected static $index_model;
    protected static $word_model;

    public function onAdd($page_id)
    {
        $this->indexPage($page_id, false);
    }

    public function onUpdate($page_id)
    {
        $this->indexPage($page_id);
    }

    /**
     * @return siteSearchPluginIndexModel
     */
    protected function getIndexModel()
    {
        if (!self::$index_model) {
            self::$index_model = new siteSearchPluginIndexModel();
        }
        return self::$index_model;
    }

    /**
     * @return siteSearchPluginWordModel
     */
    protected function getWordModel()
    {
        if (!self::$word_model) {
            self::$word_model = new siteSearchPluginWordModel();
        }
        return self::$word_model;
    }

    public function indexPage($page_id, $update = true)
    {
        $index_model = $this->getIndexModel();

        if (is_array($page_id)) {
            $p = $page_id;
            $page_id = $p['id'];
        } else {
            $page_model = new sitePageModel();
            $p = $page_model->getById($page_id);
        }

        $index = array();
        foreach (array('name', 'title', 'content') as $k) {
            $v = $p[$k];
            if ($k != 'name') {
                $v = strip_tags(str_replace('><', '> <', $v));
            }
            if ($v) {
                $this->addToIndex($index, $v, $k);
            }
        }

        if ($update) {
            $index_model->deleteByField('page_id', $page_id);
        }

        if ($index) {
            $data = array();
            foreach ($index as $word_id => $weight) {
                $data[] = array(
                    'page_id' => $p['id'],
                    'word_id' => $word_id,
                    'weight' => $weight
                );
            }
            $index_model->multipleInsert($data);
        }
    }

    protected function addToIndex(&$index, $strings, $type, $split = true)
    {
        $temp_index = array();
        $weight = $this->getWeight($type);
        foreach ((array)$strings as $string) {
            if (is_array($string)) {
                $this->addToIndex($index, $string, $type, $split);
                continue;
            }
            $words = $this->getWordIds($string);
            if (!$split && count($words) > 1) {
                $index_weight = round($weight / count($words));
                $this->addWordToIndex($temp_index, $this->getWordModel()->getId($string), $weight);
            } else {
                $index_weight = $weight;
            }
            foreach ($words as $word_id) {
                $this->addWordToIndex($temp_index, $word_id, $index_weight, true);
            }
        }
        foreach ($temp_index as $word_id => $weight) {
            $this->addWordToIndex($index, $word_id, $weight);
        }
    }

    protected function addWordToIndex(&$index, $word_id, $weight, $set_max_for_existed = false)
    {
        if (isset($index[$word_id])) {
            if ($set_max_for_existed) {
                $index[$word_id] = max($index[$word_id], $weight);
            } else {
                $index[$word_id] += $weight;
            }
        } else {
            $index[$word_id] = $weight;
        }
    }

    protected function getWordIds($string)
    {
        $words = preg_split("/([\s,;]+|[\.!\?](\s+|$))/su", $string, null, PREG_SPLIT_NO_EMPTY);
        $additional_words = array();
        foreach ($words as $i => $w) {
            $w = trim($w, '.«»"()/');
            if ($w) {
                $words[$i] = mb_strtolower($w);
                if ($word_forms = $this->getWordForms($words[$i])) {
                    $additional_words = array_merge($additional_words, $word_forms);
                }
            } else {
                unset($words[$i]);
            }
        }
        if ($additional_words) {
            $words = array_merge($words, $additional_words);
        }
        $words = array_unique($words);
        $result = array();
        $word_model = $this->getWordModel();
        foreach ($words as $w) {
            if ($w) {
                if ($w_id = $word_model->getId(self::stem($w))) {
                    $result[] = $w_id;
                }
            }
        }
        return array_unique($result);
    }

    protected function getWordForms($word)
    {
        $result = array();
        if (strpbrk($word, '/-.') !== false) {
            $result = preg_split("/[\/\.-]/u", $word, null, PREG_SPLIT_NO_EMPTY);
            if ($result) {
                $n = count($result);
                $w = $result[0];
                for ($i = 1; $i < $n; $i++) {
                    $result[$i] = trim($result[$i], '"()');
                    $w .= $result[$i];
                    if ($w) {
                        $result[] = $w;
                    }
                }
            }
        }
        if (preg_match_all('/[0-9]+/is', $word, $matches)) {
            foreach ($matches[0] as $w) {
                $result[] = $w;
            }
        }
        return $result;
    }

    protected function getWeight($type)
    {
        switch ($type) {
            case 'name':
                return 100;
            case 'title':
                return 20;
            case 'content':
            default:
                return 10;
        }
    }

    public function onDelete($page_id)
    {
        $index_model = new siteSearchPluginIndexModel();
        $index_model->deleteByField('page_id', $page_id);
    }

    public function search($query, $offset, $limit, $domain_id, $status = 1)
    {
        $index_model = new siteSearchPluginIndexModel();
        $word_model = new siteSearchPluginWordModel();
        $word_ids = $word_model->getByString($query);
        return $index_model->getPages($word_ids, $offset, $limit, $domain_id, $status);
    }

    public static function stem($word)
    {
        if (preg_match('/^[а-я]+$/ui', $word)) {
            return siteSearchPluginStemmerRU::stem($word);
        }  elseif (preg_match('/^[a-z]+$/i', $word)) {
            return siteSearchPluginStemmerEN::stem($word);
        } else {
            return $word;
        }
    }
}