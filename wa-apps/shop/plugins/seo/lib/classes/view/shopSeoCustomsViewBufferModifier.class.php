<?php


class shopSeoCustomsViewBufferModifier
{
	/** @var shopSeoCustomViewBufferModifier[] */
	private $modifiers = array();
	
	public function addModifier(shopSeoCustomViewBufferModifier $modifier)
	{
		$this->modifiers[] = $modifier;
	}
	
	public function modify(shopSeoViewBuffer $view_buffer)
	{
		foreach ($this->modifiers as $modifier)
		{
			$modifier->modify($view_buffer);
		}
	}
}