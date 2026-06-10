<?php

class shopSearchproBrandsFinder extends shopSearchproEntityFinder implements shopSearchproEntityFinderInterface
{
	protected $env;
	private $finder;

	protected function getEnv()
	{
		if(!isset($this->env)) {
			$this->env = new shopSearchproEnv();
		}

		return $this->env;
	}

	/**
	 * @return shopSearchproEntityFinderInterface
	 */
	private function getFinder()
	{
		if(!isset($this->finder)) {
			$plugin = $this->getParams('brands_plugin');
			$type = ucfirst($plugin);

			$class = "shopSearchproBrands{$type}Finder";

			if(class_exists($class)) {
				$this->finder = new $class($this->getParams());
			}
		}

		return $this->finder;
	}

	protected function getDbSelectQuery()
	{
		$model = $this->getModel();

		$select = "SELECT m.id, m.name, m.url AS brand_url FROM {$model->getTableName()} AS m";

		return $select;
	}

	/**
	 * Поиск по брендам
	 * @param string $query
	 * @param int|null $limit
	 * @return array
	 */
	public function findEntities($query, $limit = null)
	{
		$finder = $this->getFinder();

		return $finder->findEntities($query, $limit);
	}
}