<?php


class shopSeoRequestHandlerService
{
	/** @var shopSeoRequestHandlerChecker[] */
	private $checkers = array();
	/** @var shopSeoRequestHandler */
	private $handler;
	
	public function getHandler()
	{
		return $this->handler;
	}
	
	public function setHandler(shopSeoRequestHandler $handler)
	{
		$this->handler = $handler;
	}
	
	public function addChecker(shopSeoRequestHandlerChecker $checker)
	{
		$this->checkers[] = $checker;
	}
	
	public function applyInner()
	{
		if (!$this->check())
		{
			return;
		}
		
		$this->getHandler()->applyInner();
	}
	
	public function applyOuter()
	{
		if (!$this->check())
		{
			return;
		}
		
		$this->getHandler()->applyOuter();
	}
	
	private function check()
	{
		$handler = $this->getHandler();
		
		if (is_null($handler))
		{
			return false;
		}
		
		foreach ($this->checkers as $checker)
		{
			if (!$checker->check($handler))
			{
				return false;
			}
		}
		
		return true;
	}
}