<?php


class siteSearchPluginBackendIndexController extends waLongActionController {

    public function execute()
    {
        try {
            parent::execute();
        } catch (waException $ex) {
            if ($ex->getCode() == '302') {
                echo json_encode(array('warning' => $ex->getMessage()));
            } else {
                echo json_encode(array('error' => $ex->getMessage()));
            }
        }
    }

    public function init()
    {
        $model = new sitePageModel();
        $this->data['timestamp'] = time();
        $this->data['total_count'] = $model->countAll();
        $this->data['offset'] = 0;
    }

    protected function info()
    {
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time'       => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId'  => $this->processId,
            'progress'   => 0.0,
            'ready'      => $this->isDone(),
            'offset' => $this->data['offset'],
        );
        $response['progress'] = ($this->data['offset'] / $this->data['total_count']) * 100;
        $response['progress'] = sprintf('%0.3f%%', $response['progress']);

        if ($this->getRequest()->post('cleanup')) {
            //$response['report'] = $this->report();
        }

        echo json_encode($response);
    }

    public function step()
    {

        $page_model = new sitePageModel();
        $search = new siteSearchPluginIndex();

        $i = 0;
        $pages = $page_model->select('*')->limit($this->data['offset'].", 10")->fetchAll('id');
        foreach ($pages as $p) {
            $search->indexPage($p, false);
            $i++;
        }
        $this->data['offset'] += $i;
    }

    public function isDone()
    {
        return $this->data['offset'] >= $this->data['total_count'];
    }

    public function finish($filename)
    {
        $this->info();

        if($this->isDone()) return true;
        return false;
    }
}