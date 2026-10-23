<?php

class shopSearchproGramsModel extends waModel
{
	protected $table = 'shop_searchpro_grams';

	public function getWord($word, $grams, $search_type = 'like')
	{
		$word = $this->escape($word);
		$word_length = mb_strlen($word);

		// Prefix typing / short tokens: never run legacy multi-LIKE scoring
		// (Creating sort index on full grams table — multi-second queries).
		if ($search_type === 'like' && $word_length > 0 && $word_length < 5) {
			return $this->getWordByPrefix($word);
		}

		// Prefer FULLTEXT; for remaining 'like' settings use prefix fallback only.
		if ($search_type !== 'match_against') {
			$ft = $this->getWordByMatchAgainst($word, $grams);
			if ($ft !== null) {
				return $ft;
			}
			if ($search_type === 'like' && $word_length >= 5) {
				return $this->getWordByPrefix($word);
			}
			return null;
		}

		return $this->getWordByMatchAgainst($word, $grams);
	}

	/**
	 * Cheap prefix lookup (uses INDEX on word when present).
	 * @param string $word already escaped
	 * @return string|null
	 */
	private function getWordByPrefix($word)
	{
		$sql = <<<SQL
SELECT `word`
	FROM {$this->getTableName()} AS g
WHERE g.word LIKE s:prefix
ORDER BY
	`frequency` DESC,
	(g.type = 'products' AND g.subtype = 'name') DESC,
	CHAR_LENGTH(g.word) ASC
LIMIT 0, 1
SQL;
		$query = $this->query($sql, array(
			'prefix' => $word . '%',
		));
		$found = $query->fetchField('word');
		return ifempty($found, null);
	}

	/**
	 * @param string $word already escaped
	 * @param string $grams
	 * @return string|null
	 */
	private function getWordByMatchAgainst($word, $grams)
	{
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
		$found = $query->fetchField('word');
		return ifempty($found, null);
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
