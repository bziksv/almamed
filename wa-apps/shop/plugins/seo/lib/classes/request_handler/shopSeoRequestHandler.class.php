<?php


interface shopSeoRequestHandler
{
	public function getType();
	
	public function applyInner();
	
	public function applyOuter();
}