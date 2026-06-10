<?php

class shopIcPluginCountCli extends waCliController
{
    protected $public_path;
    protected $protected_path;

    public function __construct()
    {
        $this->public_path = wa()->getDataPath('products', true, 'shop');
        $this->protected_path = wa()->getDataPath('products', false, 'shop');
    }

    public function execute()
    {
        $protected_files = new shopIcRecursiveImageFinder($this->protected_path);

        $count_files = $protected_files->getCount();

        print "Protected images count: " . $count_files . "\n";
    }
}
