<?php

class shopSeofilterStorefrontFieldsModel extends waModel
{
	protected $table = 'shop_seofilter_storefront_fields';

	private static $_fields = null;

	public static function getAllFields()
	{
		if (self::$_fields === null)
		{
			self::$_fields = array();

			$model = new self;
			$rows = $model->getAll();
			foreach ($rows as $row)
			{
				self::$_fields[$row['id']] = $row['name'];
			}
		}

		return self::$_fields;
	}

	public function getFields()
	{
		$rows = $this->getAll();
		$result = array();

		foreach ($rows as $row)
		{
			$result[$row['id']] = $row['name'];
		}

		return $result;
	}

	public function setFields($_fields)
	{
		$this->deleteAll();

		foreach ($_fields as $id => $name)
		{
			$row = array(
				'id' => $id,
				'name' => $name,
			);
			$this->replace($row);
		}
	}

	public function deleteAll()
	{
		$this->query('delete from `' . $this->table . '`');
	}
}