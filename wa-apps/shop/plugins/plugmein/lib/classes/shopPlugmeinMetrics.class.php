<?php

/**
 * Class shopPlugmeinMetrics
 * Send anonymous user stats
 */
class shopPlugmeinMetrics
{
    private $apps = ['shop', 'mylang'];
    private $data = [];
    private $id; //uuid
    private $mysql_version;
    private $last_stat;
    const DEMOLLC_STAT_SERVER = 'https://stat.demollc.pw/';
    const period = 604800;

    public function __construct()
    {
        $model = new waAppSettingsModel();
        $id = $model->get('shop.plugmein', 'uuid');
        if (!$id) {
            $id = waString::uuid();
            $model->set('shop.plugmein', 'uuid', $id);
        }
        $this->last_stat = $model->get('shop.plugmein', 'last_date');
        $this->id = $id;
    }

    public function sendBeacon()
    {
        if ($this->checkDate()) {
            $data = $this->getData();
            $options = [
                'format' => waNet::FORMAT_JSON,
                'request_format' => waNet::FORMAT_JSON,
                'timeout' => '5',
            ];
            $net = new waNet($options);
            $net->query(self::DEMOLLC_STAT_SERVER, $data, waNet::METHOD_POST);
            $model = new waAppSettingsModel();
            $model->set('shop.plugmein', 'last_date', time());
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     * @throws waException
     */
    public function getData()
    {
        $this->data['system'] = $this->getSystemData();
        foreach ($this->apps as $app) {
            $method = 'get'.ucfirst($app).'Data';
            if (method_exists($this, $method) && wa()->appExists($app)) {
                $this->data['apps'][$app] = $this->$method();
            }
        }
        return $this->data;
    }


    /**
     * @return array
     * @throws waException
     */
    private function getSystemData()
    {
        return [
            'php_version' => str_replace(PHP_EXTRA_VERSION, '', PHP_VERSION),
            'mysql_version' => $this->getMysqlVersion(),
            'utfmb4' => $this->mb4IsSupported(),
            'id' => $this->id,
            'timestamp' => time(),
            'locales' => waLocale::getAll(),
            'is_cloud' => wa()->appExists('hosting'),
        ];
    }

    /**
     * @return array
     * @throws waDbException
     * @throws waException
     */
    private function getMylangData()
    {
        wa('mylang');
        $mm = new mylangModel();
        return [
            'id' => 'mylang',
            'app' => 'mylang',
            'version' => wa()->getVersion('mylang'),
            'counters' => $mm->getCount(),
        ];
    }

    /**
     * @return array
     * @throws waDbException
     * @throws waException
     */
    private function getShopData()
    {
        $pm = new shopProductModel();
        $fm = new shopFeatureModel();
        $this->getShopPlugins();
        return [
            'id' => 'shop',
            'app' => 'shop',
            'version' => wa()->getVersion('shop'),
            'products' => $pm->countAll(),
            'features' => $fm->countAll(),
        ];
    }

    private function getShopPlugins()
    {
        $plugins = wa('shop')->getConfig()->getPlugins();
        foreach ($plugins as $id=>$p) {
            if ($p['vendor'] === '991739') {
                $this->data['plugins'][$id] = [
                    'id' => $id,
                    'app' => 'shop',
                    'version' => ifset($p, 'version', 0)
                ];
            }
        }
    }

    /**
     * @return mixed|null
     * @throws waDbException
     * @throws waException
     */
    protected function getMysqlVersion()
    {
        if (!$this->mysql_version) {
            $model = new waModel();
            $version = $model->query('SELECT VERSION()')->fetchRow();
            $this->mysql_version = !empty($version[0]) ? $version[0] : null;

        }
        return $this->mysql_version;
    }

    /**
     * @return bool
     */
    protected function mb4IsSupported()
    {
        return version_compare($this->mysql_version, '5.5.3', '>=');
    }

    private function checkDate()
    {
        return empty($this->last_stat)||$this->last_stat+$this::period > time();
    }
}
