<?php

class shopSeofilterPluginDeleteGeneratedController extends shopSeofilterLongActionController
{
	const STEP_DELETE_OPERATIONS_LIMIT = 200;

	const ERROR_FILTERS_REMAINS = 1;
	const ERROR_GENERATOR_NOT_EXISTS = 2;

	protected function init()
	{
		$generator_ids = waRequest::post('generator_ids', array());
		$this->data['generator_ids'] = array();
		$this->data['generator_filter_ids'] = array();
		$this->data['deleted_generator_ids'] = array();

		$this->data['errors'] = array();
		$this->data['not_deleted'] = array();

		$history_ar = new shopSeofilterGeneratorHistory();
		$filter_model = new shopSeofilterFilterModel();

		foreach ($generator_ids as $generator_id)
		{
			$history = $history_ar->getByGeneratorId($generator_id);
			if (!$history)
			{
				continue;
			}

			$filter_ids = $filter_model
				->select('id')
				->where('generator_process_id = \'' . $filter_model->escape($history->generator_id) . '\'')
				->fetchAll('id');

			$this->data['generator_ids'][$generator_id] = 1;
			$this->data['generator_filter_ids'][$generator_id] = $filter_ids;
			$this->data['not_deleted'][$generator_id] = array();
		}

		$this->data['total'] = 0;
		foreach ($this->data['generator_filter_ids'] as $generator_id => $filter_ids)
		{
			$this->data['total'] += count($filter_ids);
		}
	}

	/**
	 * @return boolean whether all the work is done
	 */
	protected function isDone()
	{
		return count($this->data['generator_filter_ids']) == 0;
	}

	/**
	 * @return boolean false to end this Runner and call info(); true to continue.
	 */
	protected function step()
	{
		$filter_ar = new shopSeofilterFilter();
		$history_ar = new shopSeofilterGeneratorHistory();

		$step_limit = self::STEP_DELETE_OPERATIONS_LIMIT;

		$generator_ids = array_keys($this->data['generator_filter_ids']);
		$generator_id = reset($generator_ids);

		$deleted = array();

		while ($step_limit > 0)
		{
			if ($generator_id === false)
			{
				break;
			}

			foreach ($this->data['generator_filter_ids'][$generator_id] as $filter_id => $v)
			{
				$filter = $filter_ar->getById($filter_id);

				if (!$filter)
				{
					continue;
				}

				try
				{
					$success = $filter->delete();
				}
				catch (Exception $e)
				{
					$success = false;
				}

				if ($success)
				{
					$deleted[] = $filter_id;
				}
				else
				{
					$this->data['not_deleted'][$generator_id][] = $filter_id;
				}

				if ($step_limit <= 0)
				{
					break 2;
				}

				$step_limit--;
			}

			$history = $history_ar->getByGeneratorId($generator_id);

			if (!$history)
			{
				$this->data['errors'][$generator_id] = array(
					'code' => self::ERROR_GENERATOR_NOT_EXISTS,
					'message' => 'Генератора с таким id не существует',
					'data' => $generator_id,
				);
			}
			elseif (count($this->data['not_deleted'][$generator_id]) || $history->haveFilters())
			{
				$this->data['errors'][$generator_id] = array(
					'code' => self::ERROR_FILTERS_REMAINS,
					'message' => 'Не все фильтры удалены',
					'data' => $this->data['not_deleted'][$generator_id],
				);
			}
			else
			{
				$this->data['deleted_generator_ids'][] = $history->generator_id;
				$history->delete();
			}

			$deleted = array();
			unset($this->data['generator_filter_ids'][$generator_id]);

			$generator_ids = array_keys($this->data['generator_filter_ids']);
			$generator_id = reset($generator_ids);

			if ($generator_id === false)
			{
				break;
			}

			$step_limit--;
		}

		if ($generator_id !== false && array_key_exists($generator_id, $this->data['generator_filter_ids']))
		{
			foreach ($deleted as $filter_id)
			{
				unset($this->data['generator_filter_ids'][$generator_id][$filter_id]);
			}
		}

		return false;
	}

	/**
	 * @param $filename string full path to resulting file
	 * @return boolean true to delete all process files; false to be able to access process again.
	 */
	protected function finish($filename)
	{
		return !!waRequest::post('finish', false);
	}

	protected function info()
	{
		$this->prepareInfoResponse();
	}

	protected function infoReady($filename)
	{
		$this->prepareInfoResponse();
	}

	private function prepareInfoResponse()
	{
		$total = $this->data['total'];

		$processed = $total;
		foreach ($this->data['generator_filter_ids'] as $generator_id => $filter_ids)
		{
			$processed -= count($filter_ids);
		}

		$deleted = $processed;
		foreach ($this->data['not_deleted'] as $id => $filter_ids)
		{
			$deleted -= count($filter_ids);
		}

		$response = array(
			'processId' => $this->processId,
			'ready' => $this->isDone(),
			'deleted_generator_ids' => $this->data['deleted_generator_ids'],

			'progress' => array(
				'progress' => $total == 0 ? 100 : $processed / $total * 100,
				'total' => $total,
				'processed' => $processed,
				'deleted' => $deleted,
				'errors' => $this->data['errors'],
				'not_deleted' => $this->data['not_deleted'],
			),
		);

		$this->getResponse()->addHeader('Content-type', 'application/json');
		$this->getResponse()->sendHeaders();
		echo json_encode($response);
	}
}