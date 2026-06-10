<?php


class shopSeoPluginCleanController extends waController
{
	public function execute()
	{
		$cleaner = new shopSeoCleaner();
		$cleaner->clean();

		echo "ok";
	}
}