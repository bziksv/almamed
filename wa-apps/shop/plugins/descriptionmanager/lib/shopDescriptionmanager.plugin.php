<?php


class shopDescriptionmanagerPlugin extends shopPlugin
{
    /**
     * @var waSmarty3View|waView
     */
    protected $view;

    /**
     * @var shopDescriptionmanagerModel
     */
    protected $model;


    public function __construct($info)
    {
        parent::__construct($info);

        $this->view = $view = wa()->getView();

        $this->model = new shopDescriptionmanagerModel();
    }


    public function backendProductDescription($product)
    {
        $data = $this->model->getById($product['id']);

        if(!$data)
            $data['product_id'] = $product['id'];

        $this->view->assign('data', $data);

        return [
            'edit_basics'   => $this->view->fetch($this->path.'/templates/backend.html'),
        ];
    }

    public function frontendProduct($product)
    {
        if (!self::checkUser(wa()->getUser()->getId()))
            return false;

        $data = $this->model->getById($product['id']);
        $data['manager_description'] = "";
        $data['product_id'] = $product['id'];
		
		if (isset($product["features"]["brend"])) {
				$data['manager_description'] = $this->getBrendManagerDesc($product["features"]["brend"]);
		}
		
        $this->view->assign('data', $data);

        return [
            'block_aux' => $this->view->fetch($this->path.'/templates/frontend_product.html'),
        ];
    }

    public static function getById($product_id){

        $model = new shopDescriptionmanagerModel();
        return $model->getById($product_id);
    }

    public static function checkUser($user_id){

        $plugin = wa('shop')->getPlugin('descriptionmanager');
        $settings = $plugin->getSettings();
        $userIds = explode(',', $settings['descriptionmanager_user']);

        if (!in_array($user_id, $userIds))
            return false;
        else
            return true;
    }
	
	private function getBrendManagerDesc($name = "") 
	{	
		if ($brend = $this->getBrendByName($name)) {
			return $brend["seo_description"];
		}
		
		return "";
	}
	
	private function getBrendByName($name = "") 
	{	
		return (new shopProductbrandsModel())->getByField('name', $name);
	}

}
