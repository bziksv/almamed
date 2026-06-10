<?php


interface shopSeoStorefrontSource
{
	/**
	 * @return string[]
	 */
	public function getStorefronts();
	
	public function getCurrentStorefront();
}