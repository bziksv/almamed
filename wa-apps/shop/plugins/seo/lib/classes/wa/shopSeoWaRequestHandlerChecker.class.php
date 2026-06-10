<?php


class shopSeoWaRequestHandlerChecker implements shopSeoRequestHandlerChecker
{
	public function check(shopSeoRequestHandler $handler)
	{
		$type = $handler->getType();
		$is_agree = wa()->event(array('shop', 'seo_assign_case'), $type);
		
		foreach ($is_agree as $plugin_id => $_is_agree)
		{
			if (!$_is_agree)
			{
				return false;
			}
		}
		
		return true;
	}
}