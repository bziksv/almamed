<?php

class shopSeoLayoutsCollection
{
	private $keys;
	private $heap = array();
	private $logs = array();
	
	public function __construct($keys)
	{
		$this->keys = $keys;
	}
	
	
	public function push($data, $priority, $comment)
	{
		$this->logs[] = array(
			'data' => $data,
			'priority' => $priority,
			'comment' => $comment,
		);
		
		foreach ($data as $name => $value)
		{
			if (!in_array($name, $this->keys))
			{
				continue;
			}
			
			if ($this->isEmpty($value))
			{
				continue;
			}
			
			if (array_key_exists($name, $this->heap) && $priority <= $this->heap[$name]['priority'] + 1)
			{
				continue;
			}
			
			$this->heap[$name] = array(
				'value' => $value,
				'priority' => $priority,
				'comment' => $comment,
			);
		}
	}
	
	public function getResult()
	{
		$result = array_fill_keys($this->keys, '');
		
		foreach ($this->heap as $name => $data)
		{
			$result[$name] = $data['value'];
		}
		
		return $result;
	}
	
	public function getInfo()
	{
		return array(
			'logs' => $this->logs,
			'heap' => $this->heap,
		);
	}

	private function isEmpty($template)
	{
		return !is_string($template) || trim(strip_tags($template, '<img>')) === '';
	}
}