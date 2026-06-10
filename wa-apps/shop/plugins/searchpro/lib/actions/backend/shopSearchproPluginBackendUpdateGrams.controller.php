<?php

class shopSearchproPluginBackendUpdateGramsController extends waLongActionController
{
	const ENTITIES_PER_STEP = 20;

	private $types = array(
		'products', 'categories'
	);

	private $updaters;
	private $grams_count = null;

	public function execute()
	{
		try {
			parent::execute();
		} catch(waException $e) {
			if($e->getCode() == '302') {
				echo json_encode(array('warning' => $e->getMessage()));
			} else {
				echo json_encode(array('error' => $e->getMessage()));
			}
		}
	}

	/**
	 * @param string $type
	 * @return shopSearchproGramsUpdater
	 */
	private function getUpdater($type)
	{
		if(!isset($this->updaters[$type])) {
			$this->updaters[$type] = new shopSearchproGramsUpdater($type);
		}

		return $this->updaters[$type];
	}

	private function getEntityCount($type)
	{
		if(array_key_exists("count_$type", $this->data)) {
			return $this->data["count_$type"];
		}

		return null;
	}

	private function setEntityCount($type, $count)
	{
		$this->data["count_$type"] = $count;
	}

	private function getEntityOffset($type)
	{
		if(array_key_exists("offset_$type", $this->data)) {
			return $this->data["offset_$type"];
		}

		return null;
	}

	private function setEntityOffset($type, $count)
	{
		$this->data["offset_$type"] = $count;
	}

	private function increaseEntityOffset($type, $length)
	{
		$this->data["offset_$type"] = $this->data["offset_$type"] + $length;
	}

	private function isEntityDone($type)
	{
		return $this->getEntityOffset($type) >= $this->getEntityCount($type);
	}

	protected function init()
	{
		$this->data['offset'] = 0;

		/**
		 * Очищаем существующие N-граммы
		 */
		$grams_model = new shopSearchproGramsModel();
		$grams_model->clearGrams();

		foreach($this->types as $type) {
			$updater = $this->getUpdater($type);

			$this->setEntityCount($type, $updater->getEntityCount());
			$this->setEntityOffset($type, 0);
		}
	}

	/**
	 * @return bool
	 */
	protected function isDone()
	{
		foreach($this->types as $type) {
			if($this->isEntityDone($type))
				continue;
			else
				return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	protected function step()
	{
		foreach($this->types as $type) {
			if($this->isEntityDone($type))
				continue;

			$offset = $this->getEntityOffset($type);
			//$this->setEntityOffset($type, 0);

			$updater = $this->getUpdater($type);

			$length = $updater->update($offset, self::ENTITIES_PER_STEP);

			$this->increaseEntityOffset($type, $length);
		}

		return false;
	}

	protected function info()
	{
		$global_count = 0;
		$global_offset = 0;

		foreach($this->types as $type) {
			$entity_count = $this->getEntityCount($type);

			$global_count += $entity_count;
			$global_offset += $this->getEntityOffset($type);
		}

		$response = array(
			'processId' => $this->processId,
			'ready' => $this->isDone(),
			'count' => $global_count,
			'offset' => $global_offset,
			'grams_count' => $this->grams_count,
			'progress' => round(empty($global_count) ? 0 : ($global_offset / $global_count) * 100, 2)
		);

		echo json_encode($response);
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	protected function finish($filename)
	{
		if(waRequest::post('finish')) {
			$grams_model = new shopSearchproGramsModel();
			$this->grams_count = $grams_model->count();
		}

		$this->info();

		return !!waRequest::post('finish');
	}
}