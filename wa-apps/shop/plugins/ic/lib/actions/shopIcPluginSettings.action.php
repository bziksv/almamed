<?php

class shopIcPluginSettingsAction extends waViewAction
{
    protected $public_path;
    protected $protected_path;

    public function __construct()
    {
        $this->public_path = wa()->getDataPath('products', true, 'shop');
        $this->protected_path = wa()->getDataPath('products', false, 'shop');

        parent::__construct();
    }

    public function execute() {
        set_time_limit(0);

        ini_set('memory_limit', '2048M');

        $protected_files = new shopIcRecursiveImageFinder($this->protected_path);

        $this->view->assign('protected', $this->getInfo($protected_files));
    }

    protected function getInfo(shopIcRecursiveImageFinder $ImageFinder): array
    {
        $info = [];

        $info['path'] = $ImageFinder->getPath();
        $info['count'] = $ImageFinder->getCount();
        $info['size'] = round($ImageFinder->getSize() / 1024, 2);

        return $info;
    }
}