<?php


interface shopSeoViewBuffer
{
	public function getVars();
	
	public function assign($name, $value = null);
	
	public function render($template);
	
	public function renderAll($templates);
}