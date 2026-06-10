<?php


abstract class shopSeoImportexportController extends waLongActionController
{
	abstract protected function getInfo();
	
	/** Called by a Messenger when the Runner is still alive, or when a Runner
	 * exited voluntarily, but isDone() is still false.
	 *
	 * This function must send $this->processId to browser to allow user to continue.
	 *
	 * $this->data is read-only. $this->fd is not available.
	 */
	protected function info()
	{
		$this->sendInfo($this->getInfo());
	}
	
	protected function infoReady($filename)
	{
		$this->sendInfo(array_merge($this->getInfo(), array(
			'ready' => true,
		)));
	}
	
	/**
	 * Called when $this->isDone() is true
	 * $this->data is read-only, $this->fd is not available.
	 *
	 * $this->getStorage() session is already closed.
	 *
	 * @param $filename string full path to resulting file
	 * @return boolean true to delete all process files; false to be able to access process again.
	 */
	protected function finish($filename)
	{
		//$this->infoReady($filename);
		
		return false;
	}
	
	private function sendInfo($info)
	{
		$this->getResponse()->addHeader('Content-Type', 'application/json');
		$this->getResponse()->sendHeaders();
		
		echo json_encode($info);
	}
}