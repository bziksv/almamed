<?php

class shopSeofilterLayoutsCollection
{
	const NONE_PRIORITY = -1;
	const BASIC_PRIORITY = 0;
	const ONLY_EMPTY_BASIC_PRIORITY = 1;
	const OVERWRITE_BASIC_PRIORITY = 2;

	protected $layouts = array();

	public function push(array $layout, $priority_default = self::BASIC_PRIORITY, array $priorities = array())
	{
		foreach ($layout as $name => $template)
		{
			$priorities[$name] = isset($priorities[$name]) ? $priorities[$name] : $priority_default;
		}

		$this->layouts[] = array(
			'layout' => $layout,
			'priorities' => $priorities,
		);
	}

	public function getUpperItems()
	{
		$uppers = array();

		foreach ($this->layouts as $layout_data)
		{
			foreach ($layout_data['layout'] as $field => $template)
			{
				if (!isset($uppers[$field]))
				{
					$uppers[$field] = array(
						'value' => '',
						'priority' => self::NONE_PRIORITY,
					);
				}

				if (!$this->isEmpty($template))
				{
					$priority = $layout_data['priorities'][$field];
					$upper = &$uppers[$field];
					$is_rewrite = false;

					if ($upper['priority'] == self::NONE_PRIORITY)
					{
						$is_rewrite = true;
					}
					elseif ($upper['priority'] == self::BASIC_PRIORITY)
					{
						if ($priority == self::OVERWRITE_BASIC_PRIORITY)
						{
							$is_rewrite = true;
						}
					}

					if ($is_rewrite)
					{
						$upper = array(
							'value' => $template,
							'priority' => $priority,
						);
					}
				}
			}
		}

		unset($upper);
		$result = array();

		foreach ($uppers as $field => $upper)
		{
			$result[$field] = $upper['value'];
		}

		return $result;
	}

	private function isEmpty(&$template)
	{
		return !isset($template) || trim(strip_tags($template, '<img>')) === '';
	}
}