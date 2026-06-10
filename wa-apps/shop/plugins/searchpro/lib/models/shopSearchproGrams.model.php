<?php

class shopSearchproGramsModel extends waModel
{
	protected $table = 'shop_searchpro_grams';

	private function mb_str_split($string, $split_length = 2, $max_length = 2)
	{
		$string_length = mb_strlen($string);

		$arr = array();
		for($i = 0; $i <= $string_length; $i++) {
			$splitted_string = mb_substr($string, $i, $split_length);

			if(mb_strlen($splitted_string) == $max_length) {
				$arr[] = $splitted_string;
			}
		}

		return $arr;
	}

	public function getWord($word, $grams, $search_type = 'like')
	{
		$word = $this->escape($word);

		switch($search_type) {
			case 'match_against':
				$first_symbol = mb_substr($word, 0, 1);
				$first_two_symbols = mb_substr($word, 0, 2);

				$sql = <<<SQL
SELECT `word`, `frequency`, (0+IF(SUBSTRING(word, 1, 2) = s:first_two_symbols,2,IF(SUBSTRING(word, 1, 1) = s:first_symbol,1,0))) AS `symbols_relevancy`, (MATCH (`grams`) AGAINST (s:grams)) AS `match_relevancy`
	FROM {$this->getTableName()} AS g
WHERE MATCH (`grams`) AGAINST (s:grams) 
ORDER BY
	`frequency` DESC,
	`symbols_relevancy` DESC,
	`match_relevancy` DESC,
	(g.type = 'products' AND g.subtype = 'name') DESC
LIMIT 0, 1
SQL;
				$query = $this->query($sql, array(
					'grams' => $grams,
					'first_symbol' => $first_symbol,
					'first_two_symbols' => $first_two_symbols
				));
				break;
			case 'like':
				$word_length = mb_strlen($word);
				$counter = array();
				$array_grams = explode(' ', $grams);

				$array_grams_count = count($array_grams);
				$half_array_grams_count = $array_grams_count / 2;
				$double_array_grams_count = $array_grams_count * 2;
				$i = $array_grams_count;
				$n = 1;

				foreach($array_grams as $gram) {
					$gram_escaped = $this->escape($gram, 'like');
					$_n = $array_grams_count - $n;
					if(mb_strpos($gram_escaped, '_') !== false) {
						if($n <= 3) {
							$sql_gram = "$gram_escaped%";
							$counter_data = array(
								'sql' => "word LIKE '$sql_gram'",
								'true' => '+' . $double_array_grams_count,
								'false' => $n === 2 ? 0 : '-' . $double_array_grams_count,
							);

							if($n === 2) {
								$sql_where = str_replace('_', '%', $gram_escaped);
								$counter_data['where'] = "WORD LIKE '{$sql_where}'";
							}

							$counter [] = $counter_data;
						} elseif($_n < 3) {
							$sql_gram = "%$gram_escaped";
							$counter [] = array(
								'sql' => "word LIKE '$sql_gram'",
								'true' => '+' . $array_grams_count,
								'false' => 0
							);
						}
					} else {
						if($n <= 3) {
							$sql_gram = "$gram_escaped%";
							$counter [] = array(
								'sql' => "word LIKE '$sql_gram'",
								'true' => '+' . $array_grams_count,
								'false' => '-' . $array_grams_count,
							);
						} elseif($_n < 3) {
							$sql_gram = "%$gram_escaped";
							$counter [] = array(
								'sql' => "word LIKE '$sql_gram'",
								'true' => '+' . $array_grams_count,
								'false' => '-' . $array_grams_count,
							);
						}

						$counter [] = array(
							'where' => true,
							'sql' => "grams LIKE '%$gram_escaped%'",
							'true' => '+' . $i,
							'false' => '-' . $i
						);

						if($word_length <= 4) {
							$grams_two_symbols = $this->mb_str_split($gram);

							foreach($grams_two_symbols as $gram_two_symbols) {
								$gram_two_symbols_escaped = $this->escape($gram_two_symbols);
								$counter [] = array(
									'where' => true,
									'sql' => "grams LIKE '%$gram_two_symbols_escaped%'",
									'true' => '+' . ($i / 2),
									'false' => '-' . ($i / 2)
								);
							}
						}
					}

					if($n >= $half_array_grams_count) {
						$i--;
					} else {
						$i--;
					}

					$n++;
				}

				$sql_counter = '0';
				$where = '0';
				foreach($counter as $e) {
					$sql_counter .= "+if({$e['sql']}, {$e['true']}, {$e['false']})";

					if(!empty($e['where'])) {
						if($e['where'] === true) {
							$sql_where = $e['sql'];
						} else {
							$sql_where = $e['where'];
						}

						$where .= " OR {$sql_where}";
					}
				}

				$sql = <<<SQL
SELECT `word`, `type`, ({$sql_counter}) AS counter
	FROM {$this->getTableName()} AS g
WHERE ({$where})
HAVING `counter` > 2
ORDER BY
	`frequency` DESC,
	`counter` DESC,
	(g.type = 'products' AND g.subtype = 'name') DESC
LIMIT 0,1
SQL;

				$query = $this->query($sql);
				break;
		}

		if(!isset($query)) {
			return null;
		}

		$word = $query->fetchField('word');

		return ifempty($word, null);
	}

	public function getGrams($word)
	{
		return $this->getByField(array(
			'word' => $word
		));
	}

	public function addGrams($word, $grams, $type = 'general', $subtype)
	{
		return $this->insert(array(
			'word' => $word,
			'grams' => $grams,
			'type' => $type,
			'subtype' => $subtype,
			'frequency' => 1
		));
	}

	public function increaseGramsFrequency($word, $length = 1)
	{
		$grams_params = $this->getGrams($word);

		return $this->updateById($grams_params['id'], array(
			'frequency' => intval($grams_params) + $length
		));
	}

	public function countByType($type)
	{
		return $this->countByField(array(
			'type' => $type
		));
	}

	public function clearGrams()
	{
		return $this->truncate();
	}

	public function count()
	{
		return array(
			'all' => $this->countAll(),
			'general' => $this->countByType('general'),
			'products' => $this->countByType('products'),
			'categories' => $this->countByType('categories'),
			'seo_plugin' => $this->countByType('seo_plugin'),
			'seofilter_plugin' => $this->countByType('seofilters_plugin')
		);
	}
}