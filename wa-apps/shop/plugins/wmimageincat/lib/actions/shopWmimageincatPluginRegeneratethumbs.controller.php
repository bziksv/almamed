<?php
/**
* Удаление и новое создание эскизов изображений для категорий
*/
class shopWmimageincatPluginRegeneratethumbsController extends waLongActionController
{    
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
 
   protected function convert_to_array($data)
   {
        if (is_array($data)) {
            return $data;
        } else {
            $data = explode('X', $data);
            $data['width'] = array_shift($data);
            $data['height'] = array_shift($data);
            return $data;
        }
   }
  
	
	protected function finish($filename)
    {
        $this->info();
        if ($this->getRequest()->post('cleanup')) {
            return true;
        }
        return false;
    }

    protected function init()
    {
        $model = new shopWmimageincatModel();
        $plugin = wa()->getPlugin('wmimageincat');
        $query = $model->query('SELECT id, category_id, type_image, ext FROM shop_wmimageincat_images')->fetchAll();
        $this->data['image_count'] = count($query);
        $this->data['offset'] = 0;
        $this->data['timestamp'] = time();
        $this->data['data'] = $query;
        $this->data['banner'] = $this->convert_to_array($plugin->getSettings('banner'));
        $this->data['image'] = $this->convert_to_array($plugin->getSettings('image'));
        $this->data['icon'] = $this->convert_to_array($plugin->getSettings('icon'));
    }

    protected function isDone()
    {
        return $this->data['offset'] >= $this->data['image_count'];
    }

    protected function step()
    {
        sleep(0.2);
        $path = wa()->getDataPath('wmimageincatPlugin/categories/', true, 'shop');
	    $original_path = wa()->getDataPath('wmimageincatPlugin/categories/', false, 'shop');
        $ext = $this->data['data'][$this->data['offset']]['ext'];
        $type_image = $this->data['data'][$this->data['offset']]['type_image']; 
        $id = $this->data['data'][$this->data['offset']]['id'];
        $width = $this->data[$type_image]['width'];
        $height = $this->data[$type_image]['height'];
        $size = array('width' => $width, 'height' => $height);


        if (file_exists($original_path."{$this->data['data'][$this->data['offset']]['category_id']}/{$type_image}_{$id}.{$ext}")) {
            $image = shopCreatethumbnails::generateThumb($original_path."{$this->data['data'][$this->data['offset']]['category_id']}/{$type_image}_{$id}.{$ext}",
                $size);

            if (file_exists($path."{$this->data['data'][$this->data['offset']]['category_id']}/{$type_image}_{$id}.{$ext}")) {
                waFiles::delete($path."{$this->data['data'][$this->data['offset']]['category_id']}/{$type_image}_{$id}.{$ext}");
            }

            $image->save($path."{$this->data['data'][$this->data['offset']]['category_id']}/{$type_image}_{$id}.{$ext}");
        }

        $this->data['offset'] += 1;
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
        $response['progress'] = ($this->data['offset'] / $this->data['image_count']) * 100;
        $response['progress'] = sprintf('%0.3f%%', $response['progress']);
        
        if ($this->getRequest()->post('cleanup')) {
            $response['report'] = $this->report();
        }

        echo json_encode($response);
    }

    protected function report()
    {
        $report = '<div class="successmsg"><i class="icon16 yes"></i> Обработано '.$this->data['image_count'].' эскизов';

        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
            $interval = sprintf(_w('%02d hr %02d min %02d sec'), floor($interval / 3600), floor($interval / 60) % 60, $interval % 60);
            $report .= ' '.sprintf(_w('(total time: %s)'), $interval);
        }

        $report .= '&nbsp;<a class="close" href="javascript:void(0);">'._w('close').'</a></div>';
        return $report;
    }


    private function error($message)
    {
        $path = wa()->getConfig()->getPath('log');
        waFiles::create($path.'/shop/wmimageincat_thumb_regenerate.log');
        waLog::log($message, 'shop/wmimageincat_thumb_regenerate.log');
    }
}

