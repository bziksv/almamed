<?php


class shopDevPlugin extends shopPlugin
{
    public $view;

    public function __construct($info)
    {
        $this->view = wa()->getView();

        parent::__construct($info);
    }

    public static function handle()
	{
		//
		$wa_shop = new shopViewHelper(wa('shop'));
		$filtered = array_keys($wa_shop->products('category/4974', null, null, ['fields' => 'id']));
		
		$collection = new shopProductsCollection('category/4972');
		$collection->addWhere('id NOT IN (' . implode(',', $filtered) . ')');
		$products = $collection->getProducts('*', null, null, true);
		
		var_dump($products);
	}
}

