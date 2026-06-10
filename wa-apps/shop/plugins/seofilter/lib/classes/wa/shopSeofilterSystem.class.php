<?php

class shopSeofilterSystem extends waSystem
{
	public static function cleanFactories()
	{
		/** @var waSystem $system */
		foreach (waSystem::$instances as $system)
		{
			unset($system->factories['view']);
		}
	}
}