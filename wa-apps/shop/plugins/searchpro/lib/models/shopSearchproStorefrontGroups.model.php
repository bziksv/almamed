<?php

class shopSearchproStorefrontGroupsModel extends waModel
{
	protected $table = 'shop_searchpro_storefront_groups';

	public function get($id)
	{
		$storefronts = json_decode($this->query("SELECT storefronts FROM `{$this->getTableName()}` WHERE id = ?", $id)->fetchField('storefronts'), true);

		if($storefronts === null && json_last_error() !== JSON_ERROR_NONE) {
			return null;
		} else {
			return $storefronts;
		}
	}

	public function getAll($key = null, $normalize = false, $json_parse = true)
	{
		$rows = parent::getAll($key, $normalize);

		if($json_parse) {
			foreach($rows as &$row) {
				$parsed_row = json_decode($row['storefronts'], true);

				if($parsed_row === null && json_last_error() !== JSON_ERROR_NONE) {
					$row = array();
				} else {
					$row = $parsed_row;
				}
			}
		}

		return $rows;
	}

	public function set($groups)
	{
		$this->truncate();

		foreach($groups as $id => $storefronts) {
			$this->insert(array(
				'id' => $id,
				'storefronts' => json_encode(array_values(array_filter($storefronts)))
			));
		}
	}
}
