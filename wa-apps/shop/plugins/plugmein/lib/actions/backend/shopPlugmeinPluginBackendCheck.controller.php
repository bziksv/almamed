<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

class shopPlugmeinPluginBackendCheckController extends waLongActionController
{

    protected function preExecute()
    {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $this->getResponse()->sendHeaders();
    }

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

    /**
     * Initializes new process.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     */
    protected function init()
    {
        $this->data['files'] = array();
        $finder = new Finder();
        $root = wa()->getConfig()->getRootPath();
        $finder
            ->files()
            ->name('.files.md5')
            ->ignoreDotFiles(false)
            ->ignoreUnreadableDirs()
            ->in($root.DIRECTORY_SEPARATOR.'wa-apps')
            ->in($root.DIRECTORY_SEPARATOR.'wa-content')
            ->in($root.DIRECTORY_SEPARATOR.'wa-system');
        $optional_dirs = array('wa-installer', 'wa-plugins', 'wa-widgets');
        foreach ($optional_dirs as $dir) {
            if (file_exists($root.DIRECTORY_SEPARATOR.$dir)) {
                $finder->in($root.DIRECTORY_SEPARATOR.$dir);
            }
        }
        foreach ($finder as $file) {
            /** @var array $parsed */
            $parsed = $this->parseFile($file->getContents(), $file->getRealPath());
            $this->data['files'] = array_merge($this->data['files'], $parsed);
        }
        $this->data['memory'] = memory_get_peak_usage();
        $this->data['memory_avg'] = memory_get_usage();
    }

    private function parseFile($contents, $path)
    {
        $result = [];
        $re = '/([a-f0-9]{32}) \*(.*)/';
        preg_match_all($re, $contents, $matches, PREG_SET_ORDER);
        foreach ($matches as $k => $m) {
            $result[$k]['hash'] = $m[1];
            $result[$k]['path'] = dirname($path).DIRECTORY_SEPARATOR.$m[2];
        }
        return $result;
    }

    /**
     * Checks if there is any more work for $this->step() to do.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     *
     * $this->getStorage() session is already closed.
     *
     * @return boolean whether all the work is done
     */
    protected function isDone()
    {
        // TODO: Implement isDone() method.
        return count($this->data['files']) < 2;
    }

    /**
     * Performs a small piece of work.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     * Should never take longer than 3-5 seconds (10-15% of max_execution_time).
     * It is safe to make very short steps: they are batched into longer packs between saves.
     *
     * $this->getStorage() session is already closed.
     * @return boolean false to end this Runner and call info(); true to continue.
     */
    protected function step()
    {
        // TODO: Implement step() method.
        $file = array_pop($this->data["files"]);
        if (md5_file($file['path']) != $file['hash']) {
            $this->data['failed'][] = $file['path'];
        }
        return !$this->isDone();
    }

    /**
     * Called when $this->isDone() is true
     * $this->data is read-only, $this->fd is not available.
     *
     * $this->getStorage() session is already closed.
     *
     * @param $filename string full path to resulting file
     * @return boolean true to delete all process files; false to be able to access process again.
     */
    protected function finish($filename)
    {
        // TODO: Implement finish() method.
        $this->info();
    }

    /** Called by a Messenger when the Runner is still alive, or when a Runner
     * exited voluntarily, but isDone() is still false.
     *
     * This function must send $this->processId to browser to allow user to continue.
     *
     * $this->data is read-only. $this->fd is not available.
     */
    protected function info()
    {
        // TODO: Implement info() method.
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time'       => sprintf(
                '%d:%02d:%02d',
                floor($interval / 3600),
                floor($interval / 60) % 60,
                $interval % 60
            ),
            'processId'  => $this->processId,
            'ready'      => $this->isDone(),
            'count'      => count($this->data['files']),
            'progress'      => count($this->data['files']),
            'memory'     => sprintf('%0.2fMByte', $this->data['memory'] / 1048576),
            'memory_avg' => sprintf('%0.2fMByte', $this->data['memory_avg'] / 1048576),
        );
        if ($response['ready']) {
            $response['report'] = $this->report();
        }
        echo json_encode($response);
    }

    private function report()
    {
        if ($this->data['failed']) {
            $report = '<div class="errormsg"><table class="zebra">';
            $report .= sprintf('<i class="icon16 no"></i>%s ', _wp("Something wrong"));
            foreach ($this->data['failed'] as $f) {
                $report .= '<tr><td>'.$f.'</td><td>'.wa_date('humandatetime', filemtime($f)).'</td></tr>';
            }
            $report .= '</table></div>';
        } else {
            $report = '<div class="successmsg">';
            $report .= sprintf('<i class="icon16 yes"></i>%s ', _wp("All files are correct"));
            $report .= '</div>';

        }
        return $report;
    }
}
