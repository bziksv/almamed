<?php

class shopSeofilterPluginFilterGeneratorController extends shopSeofilterLongActionController
{
	const STEP_INSERT_OPERATIONS_LIMIT = 200;

	const ERROR_OTHER = 'ERROR_OTHER';
	const ERROR_DUPLICATE = 'ERROR_DUPLICATE';

	protected function init()
	{
		$request_json = waRequest::post('state');
		$request = json_decode($request_json, true);

		if (!is_array($request))
		{
			throw new waException();
		}

		$features_data = ifset($request['features'], array());

		if (!count($features_data))
		{
			$this->data['is_invalid'] = true;
			return;
		}

		$feature_ids = array();
		foreach ($features_data as $feature_data)
		{
			$feature_ids[] = $feature_data['feature_id'];
		}


		$storefronts_use_mode = ifset($request['storefronts_use_mode'], shopSeofilterFilter::USE_MODE_ALL);
		$categories_use_mode = ifset($request['categories_use_mode'], shopSeofilterFilter::USE_MODE_ALL);

		$storefronts = ifset($request['storefronts'], array());
		$categories = ifset($request['categories'], array());

		$rule_attributes = ifset($request['personal_rule'], array());

		$this->data['filter_sample_attributes'] = array(
			'filter_storefronts' => $storefronts,
			'filter_categories' => $categories,
			'storefronts_use_mode' => $storefronts_use_mode,
			'categories_use_mode' => $categories_use_mode,
		);
		$insert_operations = 1 + count($feature_ids) + count($storefronts) + count($categories);

		$rule_attributes = $this->prepareRuleAttributes($rule_attributes);
		if ($rule_attributes !== null)
		{
			$this->data['personal_rule_attributes'] = $rule_attributes;
			$insert_operations++;
		}

		$this->data['limit'] = ceil(self::STEP_INSERT_OPERATIONS_LIMIT / $insert_operations);

		if ($this->data['limit'] == 0)
		{
			$this->data['limit'] = 1;
		}

		$this->data['feature_values_groups'] = $this->generateFilterFeatureValuesGroups($features_data, $feature_ids);
		$this->data['value_seo_names'] = count($features_data) == 1 ? $features_data[0]['value_seo_names'] : null;
		$this->data['total_groups'] = count($this->data['feature_values_groups']);

		$this->data['created'] = 0;
		$this->data['skipped_info'] = array();

		$this->data['is_invalid'] = false;

		$this->writeHistory($features_data);
	}

	private function getFilterSample()
	{
		$filter = new shopSeofilterFilter($this->data['filter_sample_attributes']);

		if (isset($this->data['personal_rule_attributes']))
		{
			$rule = new shopSeofilterFilterPersonalRule($this->data['personal_rule_attributes']);
			$filter->personalRules = array($rule);
		}

		return $filter;
	}

	/**
	 * @return shopSeofilterFilterFeatureValuesGroup[]
	 */
	private function getFeatureValuesGroupsChunk()
	{
		$groups = $this->data['feature_values_groups'];

		$to_process = array_splice($groups, 0, $this->data['limit']);

		$this->data['feature_values_groups'] = $groups;

		return $to_process;
	}

	/**
	 * @return boolean whether all the work is done
	 */
	protected function isDone()
	{
		return ifset($this->data['is_invalid'], true)
			? true
			: count($this->data['feature_values_groups']) == 0;
	}

	/**
	 * @return boolean false to end this Runner and call info(); true to continue.
	 */
	protected function step()
	{
		if (ifset($this->data['is_invalid'], true))
		{
			return true;
		}

		$value_seo_names = $this->data['value_seo_names'];
		$feature_values_groups = $this->getFeatureValuesGroupsChunk();
		$filter_sample = $this->getFilterSample();

		foreach ($feature_values_groups as $feature_values_group)
		{
			$seo_name = null;
			$feature_values = $feature_values_group->getGroup();

			if (
				is_array($value_seo_names)
				&& count($feature_values) == 1
				&& array_key_exists($feature_values[0]->value_id, $value_seo_names)
			)
			{
				$seo_name = $value_seo_names[$feature_values[0]->value_id];
			}

			if ($seo_name === null)
			{
				$seo_name = $feature_values_group->getName();
			}

			$filter = clone $filter_sample;
			$filter->seo_name = $seo_name;

			$filter->featureValues = $feature_values;
			$filter->url = shopSeofilterFilterUrl::generateUniqueUrl($filter);
			$filter->generator_process_id = $this->processId;

			if ($filter->save())
			{
				$this->data['created']++;
			}
			else
			{
				$errors = $filter->errors();

				if (isset($errors[shopSeofilterFilter::ERROR_KEY_FEATURE_VALUES]))
				{
					$error_id = self::ERROR_DUPLICATE;
					$error_message = 'дублирование фильтров';
				}
				else
				{
					$error_id = self::ERROR_OTHER;
					$error_message = 'ошибка сохранения';
				}

				if (!isset($this->data['skipped_info'][$error_id]))
				{
					$this->data['skipped_info'][$error_id] = array(
						'count' => 0,
						'message' => $error_message,
					);
				}

				$this->data['skipped_info'][$error_id]['count']++;
			}
		}

		unset($filter);
		unset($feature_values_group);

		return true;
	}

	/**
	 * @param $filename string full path to resulting file
	 * @return boolean true to delete all process files; false to be able to access process again.
	 */
	protected function finish($filename)
	{
		if (ifset($this->data['is_invalid'], true))
		{
			return true;
		}

		$history_ar = new shopSeofilterGeneratorHistory();
		$history = $history_ar->getByGeneratorId($this->processId);

		if ($history && $history->total === null)
		{
			$skipped = 0;
			foreach ($this->data['skipped_info'] as $info)
			{
				$skipped += $info['count'];
			}

			$history->total = $this->data['total_groups'];
			$history->created = $this->data['created'];
			$history->skipped = $skipped;

			$history->created == 0
				? $history->delete()
				: $history->save();
		}

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
		if (ifset($this->data['is_invalid'], true))
		{
			$response = array(
				'processId' => $this->processId,
				'ready' => true,
				'history_attributes' => null,

				'progress' => array(
					'progress' => 100,
					'total' => 0,
					'processed' => 0,
					'created' => 0,
					'skipped' => 0,
				),
			);

			$this->getResponse()->addHeader('Content-type', 'application/json');
			$this->getResponse()->sendHeaders();
			echo json_encode($response);
			return;
		}

		$total = $this->data['total_groups'];
		$processed = $total - count($this->data['feature_values_groups']);

		$is_done = $this->isDone();
		$response = array(
			'processId' => $this->processId,
			'ready' => $is_done,
			'history_attributes' => null,

			'progress' => array(
				'progress' => $total == 0 ? 100 : $processed / $total * 100,
				'total' => $total,
				'processed' => $processed,
				'created' => $this->data['created'],
				'skipped' => array_values($this->data['skipped_info']),
			),
		);

		if ($is_done)
		{
			$history_ar = new shopSeofilterGeneratorHistory();
			$history = $history_ar->getByGeneratorId($this->processId);

			if ($history)
			{
				$response['history_attributes'] = $history->getViewAttributes();
			}
		}

		$this->getResponse()->addHeader('Content-type', 'application/json');
		$this->getResponse()->sendHeaders();
		echo json_encode($response);
	}

	private function prepareRuleAttributes($attributes)
	{
		$default_attributes = array(
			'meta_title',
			'meta_description',
			'meta_keywords',
			'seo_h1',
			'seo_description',
		);

		$is_empty = true;
		foreach ($default_attributes as $field)
		{
			$attributes[$field] = trim(ifset($attributes[$field], ''));
			if (strlen($attributes[$field]) != 0)
			{
				$is_empty = false;
			}
		}

		return $is_empty
			? null
			: $attributes;
	}

	/**
	 * @param array $features_data
	 * @param array $feature_ids
	 * @return shopSeofilterFilterFeatureValuesGroup[]
	 * @throws waException
	 */
	private function generateFilterFeatureValuesGroups($features_data, $feature_ids)
	{
		$filter_features = array();

		$features = shopSeofilterFilterFeatureValuesHelper::getFeatures('id', $feature_ids, 'id');
		$values = array();

		foreach ($features_data as $feature_data)
		{
			$feature_id = $feature_data['feature_id'];
			if (!array_key_exists($feature_id, $features))
			{
				throw new waException();
			}

			if (!array_key_exists($feature_id, $values))
			{
				$values[$feature_id] = shopFeatureModel::getFeatureValues($features[$feature_id]->assoc());
			}

			if (!array_key_exists('selected_values', $feature_data) || !is_array($feature_data['selected_values']))
			{
				continue;
			}

			$selected_values = array();
			foreach ($feature_data['selected_values'] as $selected_value_id)
			{
				if (isset($values[$feature_id][$selected_value_id]))
				{
					$selected_values[$selected_value_id] = $values[$feature_id][$selected_value_id];
				}
			}

			$filter_features[] = array(
				'feature_id' => $feature_id,
				'value_ids' => array_keys($selected_values),
				'value_seo_names' => $feature_data['value_seo_names'],
			);
		}

		$set = shopSeofilterFeatureValuesCombiner::combine($filter_features);

		$object_groups = array();
		foreach ($set->getGroups() as $group)
		{
			$object_group = new shopSeofilterFilterFeatureValuesGroup();

			foreach ($group as $feature_id => $group_values)
			{
				foreach ($group_values as $value_id => $t)
				{
					$object_group->addFeatureValue(
						$feature_id,
						$value_id,
						shopSeofilterFilterFeatureValuesHelper::getValueName($values[$feature_id][$value_id], $features[$feature_id]['name'])
					);
				}
			}

			$object_groups[] = $object_group;
		}
		unset($object_group);

		return $object_groups;
	}

	/**
	 * @param $features_data
	 * @return shopSeofilterGeneratorHistory
	 */
	private function writeHistory($features_data)
	{
		$history = new shopSeofilterGeneratorHistory();
		$history->generator_id = $this->processId;
		$history->date = date('Y-m-d H:i:s');

		$features = array();
		foreach ($features_data as $i => $feature_data)
		{
			$feature_id = $feature_data['feature_id'];
			$features[] = new shopSeofilterGeneratorHistoryFeature(array(
				'feature_id' => $feature_id,
				'order' => $i,
			));
		}
		$history->features = $features;

		$history->save();

		return $history;
	}
}