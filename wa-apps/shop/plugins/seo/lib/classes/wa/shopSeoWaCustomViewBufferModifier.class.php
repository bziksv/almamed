<?php


class shopSeoWaCustomViewBufferModifier implements shopSeoCustomViewBufferModifier
{
	public function modify(shopSeoViewBuffer $view_buffer)
	{
		$params = $view_buffer->getVars();
		$hook_vars = wa()->event(array('shop', 'seo_fetch_templates'), $params);
		$vars = array();
		
		foreach ($hook_vars as $plugin_id => $_hook_vars)
		{
			$vars = array_merge($vars, $_hook_vars);
		}
		
		$view_buffer->assign($vars);
	}
}