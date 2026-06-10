<?php

abstract class shopSearchproComponentSettingsModel extends waModel
{
	protected $required = array();
	protected $is_save_general = true;

	final public function getSettings()
	{
		$settings = $this->getAll($this->component_id, 2);

		foreach($settings as &$component) {
			$component_settings = array();

			foreach($component as $row) {
				if(!is_numeric($row['value'])) {
					$json = json_decode($row['value'], true);
					if(is_array($json)) {
						$row['value'] = $json;
					}
				}

				$component_settings[$row['name']] = $row['value'];
			}

			$component = $component_settings;
		}

		return $settings;
	}

	final protected function getSetting($component_id, $name)
	{
		return $this->getByField(array(
			$this->component_id => $component_id,
			'name' => $name
		));
	}

	final protected function updateSetting($component_id, $name, $value)
	{
		if(!$this->is_save_general && $component_id === '*')
			return null;

		return $this->updateByField(array(
			$this->component_id => $component_id,
			'name' => $name
		), array(
			'value' => $value
		));
	}

	final protected function addSetting($component_id, $name, $value)
	{
		if(!$this->is_save_general && $component_id === '*')
			return null;

		return $this->insert(array(
			$this->component_id => $component_id,
			'name' => $name,
			'value' => $value
		));
	}

	final protected function deleteSetting($component_id, $name)
	{
		if(!$this->is_save_general && $component_id === '*')
			return null;

		return $this->deleteByField(array(
			$this->component_id => $component_id,
			'name' => $name
		));
	}

	final public function update($component_id, $settings, $is_allow_null = false)
	{
		if($settings === null)
			return;

		if(!$this->is_save_general && $component_id === '*')
			return null;

		foreach($this->required as $field) {
			if(empty($settings[$field]) && !$is_allow_null) {
				throw new Exception($field);
			}
		}

		foreach($settings as $name => $value) {
			if(is_array($value))
				$value = json_encode($value);

			if($value === null && $is_allow_null) {
				if($this->getSetting($component_id, $name)) {
					$this->deleteSetting($component_id, $name);
				}
			} elseif($value !== null) {
				if($this->getSetting($component_id, $name)) {
					$this->updateSetting($component_id, $name, $value);
				} else {
					$this->addSetting($component_id, $name, $value);
				}
			}
		}
	}
}