<?php

class shopSeofilterFilterModel extends waModel
{
	protected $table = 'shop_seofilter_filter';

	public function maxCloneIndex($clean_name)
	{
		$rows = $this
			->select($this->escapeField('seo_name'))
			->where($this->escapeField('seo_name') . ' REGEXP \'' . $this->escape($clean_name) . ' [(][0-9]+[)]\'')
			->fetchAll();

		$max_index = 0;
		foreach ($rows as $row)
		{
			if (preg_match('/ \((\d+)\)$/', $row['seo_name'], $matches))
			{
				$max_index = max($max_index, (int)$matches[1]);
			}
		}

		return $max_index;
	}

	public function getFilterUrls($filter_ids)
	{
		if (!count($filter_ids))
		{
			return array();
		}

		return $this->select('id, url')
			->where('id IN (s:ids)', array('ids' => $filter_ids))
			->query();
	}
}