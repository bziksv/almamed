<?php

class shopSeofilterPluginSitemapCacheGeneratorController extends waLongActionController
{
	const CACHE_STEP_TIME_LIMIT = 7;

	const STEP_LIMIT = 5;

	/** @var shopSeofilterSitemapCache  */
	private $sitemap_cache = null;

	public function callIsDone()
	{
		return $this->isDone();
	}

	public function callCleanup()
	{
		$this->_cleanup();
	}

	public function isLost()
	{
		return !file_exists($this->_files['new']['file'])
			|| !file_exists($this->_files['new']['data'])
			|| !file_exists($this->_files['old']['data']);
	}

	protected function preExecute()
	{
		if ($this->isCli())
		{
			return;
		}

		$user = wa()->getUser();
		$user_rights = new shopSeofilterUserRights();
		if (!$user || !$user->exists() || !$user_rights->hasRights($user))
		{
			throw new waException('Доступ запрещен', 403);
		}
	}

	protected function init()
	{
		$current_id_storage = new shopSeofilterCurrentSitemapGeneratorIdStorage();
		$current_id_storage->store($this->processId);

		$this->data['need_initialization'] = true;
	}

	protected function isDone()
	{
		if (isset($this->data['need_initialization']) && $this->data['need_initialization'])
		{
			return false;
		}

		$model = new shopSeofilterSitemapCacheQueueModel();
		$count = (int) $model->countByField('cache_generator_id', $this->processId);

		return $count == 0;
	}

	protected function step()
	{
		if (isset($this->data['need_initialization']) && $this->data['need_initialization'])
		{
			$sitemap_cache = new shopSeofilterSitemapCache($this->processId);
			$sitemap_cache->buildQueue();

			$this->data['need_initialization'] = false;

			return false;
		}

		$steps_count = isset($this->data['steps_count']) ? $this->data['steps_count'] : 0;

		$this->getSitemapCache()->step();

		if ($steps_count < $this->getStepsLimit())
		{
			$this->data['steps_count'] = ++$steps_count;

			return true;
		}
		else
		{
			$this->data['steps_count'] = 0;

			return false;
		}
	}

	protected function finish($filename)
	{
		return $this->isCli() || !!waRequest::post('finish', false);
	}

	protected function info()
	{
		if ($this->isCli())
		{
			return;
		}

		$model = new shopSeofilterSitemapCacheQueueModel();
		$count_remaining = (int) $model->countByField('cache_generator_id', $this->processId);
		$count_total = (int) $model->countAll();

		$progress = $count_total == 0
			? 100
			: ($count_total - $count_remaining) / $count_total * 100;

		$response = array(
			'process_id' => $this->processId,
			'is_done' => $this->isDone(),
			'progress' => $progress,
		);

		$this->getResponse()->addHeader('Content-type', 'application/json');
		$this->getResponse()->sendHeaders();
		echo json_encode($response);
	}

	protected function infoReady($filename)
	{
		$this->info();
	}

	protected function _getFilenames()
	{
		$dir = $this->getTemporaryFilesPath($this->processId);

		return array(
			'new' => array(
				'data' => $dir . '/new_data',
				'file' => $dir . '/new_file',
			),
			'old' => array(
				'data' => $dir . '/old_data',
				'file' => $dir . '/old_file',
			),
			'flock_ok' => $dir . '/flock_ok',
		);
	}

	/** Creates private files and $this->... data structures for new process.
	 * Initializes $this->_processId, $this->_data, $this->_fd, $this->_runner = true
	 * Called once when a process is created. */
	protected function _initDataStructures()
	{
		// Generate new unique id
		$attempts = 3;
		$dir = $this->getTemporaryFilesPath() . '/';
		do
		{
			$attempts--;
			$id = uniqid();
		}
		while ($attempts >= 0 && !@mkdir($dir . $id, 0775));

		if ($attempts <= 0)
		{
			throw new waException('Unable to create unique dir in ' . $dir);
		}

		$this->_newProcess = true;
		$this->_processId = $id;
		$this->_runner = true;

		// Create folder, locked files, unlocked files and data files
		$this->_files = $this->_getFilenames();
		touch($this->_files['new']['file']);
		touch($this->_files['old']['file']);

		// init $this->fd
		if (!($this->_fd = fopen($this->_files['new']['file'], 'a+b')))
		{
			throw new waException('Unable to open file: ' . $this->_files['new']['file']);
		}

		// $this->data is already fine, but we have to write data files
		$this->put($this->_files['new']['data'], 'garbage');

		// Allowing init() to modify $this->data before we first save it.
		$this->_transaction = true;
		$this->init();
		$this->save();
		$this->_transaction = false;
		$this->put($this->_files['old']['data'], $this->serializeData($this->_data));
	}

	protected function _cleanup()
	{
		$current_id_storage = new shopSeofilterCurrentSitemapGeneratorIdStorage();
		$current_id_storage->clear();

		parent::_cleanup();
	}

	private function getSitemapCache()
	{
		if ($this->sitemap_cache === null)
		{
			$this->sitemap_cache = new shopSeofilterSitemapCache(
				$this->processId,
				$this->_chunk_time ? $this->_chunk_time : ($this->isCli() ? self::CACHE_STEP_TIME_LIMIT : 2)
			);
		}

		return $this->sitemap_cache;
	}

	private function isCli()
	{
		return wa()->getEnv() === 'cli';
	}

	private function getStepsLimit()
	{
		return self::STEP_LIMIT;
	}

	private function getTemporaryFilesPath($process_id = null)
	{
		$path = $process_id
			? 'longop/'.$this->processId
			: 'longop';

		return $this->isCli()
			? waSystem::getInstance()->getDataPath('plugins/seofilter/' . $path, false, 'shop', true)
			: waSystem::getInstance()->getTempPath($path);
	}
}
