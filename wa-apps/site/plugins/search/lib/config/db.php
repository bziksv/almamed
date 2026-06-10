<?php
return array(
    'site_search_plugin_index' => array(
        'word_id' => array('int', 11, 'null' => 0),
        'page_id' => array('int', 11, 'null' => 0),
        'weight' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('page_id', 'word_id'),
            'word' => array('word_id', 'page_id', 'weight'),
        ),
    ),
    'site_search_plugin_word' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name' => array('name', 'unique' => 1),
        ),
    ),
);