<?php

class shopSearchproPluginFrontendPageSearchAction extends shopFrontendSearchAction
{
	private $products;

	protected $collection;
	protected $count;

	public $query;
	public $category_id;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->view = new waSmarty3View(wa());
	}

	protected function getParams()
	{
		return $this->params;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	protected function getParam($name)
	{
		if(array_key_exists($name, $this->params)) {
			return $this->params[$name];
		}

		return null;
	}

	protected function isEmpty()
	{
		return (bool) $this->getParam('is_empty');
	}

	/**
	 * @return shopSearchproResult
	 */
	protected function getProducts()
	{
		if(!isset($this->products)) {
			$this->products = $this->getParam('products');

			if(!$this->products instanceof shopSearchproResult) {
				$this->products = new shopSearchproResult(array());
			}
		}

		return $this->products;
	}

	/**
	 * @return string
	 */
	protected function getQuery()
	{
		return htmlspecialchars((string) $this->getParam('query'));
	}

	/**
	 * @return array
	 */
	protected function getCategories()
	{
		return (array) $this->getParam('categories');
	}

	public function setFilters($filters)
	{
		$this->view->assign('filters', $filters);
	}

	public function getCount()
	{
		return $this->count;
	}

	protected function setCount($count)
	{
		$this->count = $count;
	}

	/**
	 * @return shopProductsCollection
	 */
	public function getCollection()
	{
		if(!isset($this->collection)) {
			$this->collection = $this->getProducts()->getInitialCollection();
		}

		return $this->collection;
	}

	public function preExecute()
	{
	}

	public function execute()
	{
		if($this->isEmpty()) {
			$this->executeEmpty();
		} else {
			$this->executePrimary();
		}

		$this->postExecute();
	}

	public function executeEmpty()
	{
		$this->view->assign('title', 'По вашему запросу ничего не найдено');

		$collection = $this->getCollection();
		$collection->setOptions(array(
			'overwrite_product_prices' => true,
		));

		$limit = $this->getParam('limit');
		$products = $collection->getProducts('*,skus_filtered,skus_image', 0, $limit);
		$count = $collection->count();
		$this->setCount($count);

		$this->view->assign('products', $products);
		$this->view->assign('products_count', $count);
	}

	protected function filter()
	{
		$this->getCollection()->filters(waRequest::get());
	}

	public function getFilteredCollection()
	{
		$this->filter();

		$collection = $this->getCollection();

		return $collection;
	}

	public function executePrimary()
	{
		$this->view->assign('title', 'По вашему запросу ничего не найдено');

		$this->filter();
		$collection = $this->getCollection();

		$sort = waRequest::get('sort');
		if(empty($sort)) {
			$hash = $collection->getHash();
			if($hash[0] === 'id' && !empty($hash[1])) {
				$collection->orderBy("FIELD(p.id, {$hash[1]})", 'ASC');
			}
		}

		$this->setCollection($collection);
		$filtered_products_count = $this->view->getVars('products_count');
		$this->setCount($filtered_products_count);

		$query = $this->getQuery();
		$this->view->assign('title', $query);

		if($this->layout) {
			$this->layout->assign('query', $query);
		}

		if(!$query) {
			$this->view->assign('sorting', true);
		}
	}

	public function postExecute()
	{
		/**
		 * @event frontend_search
		 * @return array[string]string $return[%plugin_id%] html output for search
		 */
		$this->view->assign('frontend_search', wa()->event('frontend_search'));

		$this->setThemeTemplate('search.html');
	}

	public function addVars($vars)
	{
		$this->view->assign($vars);
	}
}