<?php

abstract class shopSearchproEntityFinder
{
	private $params;

	public function __construct($params = array())
	{
		$this->params = $params;
	}

	/**
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	protected function isSimilar($a, $b)
	{
		return mb_stripos($a, $b) !== false;
	}

	protected function getParams($name = null)
	{
		if($name === null) {
			return $this->params;
		} else {
			if(array_key_exists($name, $this->params)) {
				return $this->params[$name];
			}

			return null;
		}
	}

	protected function getModel()
	{
		return new waModel();
	}

	protected function getDbSelectQuery()
	{
		$model = $this->getModel();

		$select = "SELECT m.* FROM {$model->getTableName()} AS m";

		return $select;
	}
}