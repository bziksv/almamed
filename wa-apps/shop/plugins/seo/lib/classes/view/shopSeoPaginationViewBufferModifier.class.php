<?php


class shopSeoPaginationViewBufferModifier
{
	public function modify($page, shopSeoViewBuffer $view_buffer)
	{
		$view_buffer->assign('page_number', $page);
	}
}