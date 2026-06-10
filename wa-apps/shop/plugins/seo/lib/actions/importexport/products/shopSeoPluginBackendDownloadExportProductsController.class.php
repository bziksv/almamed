<?php


class shopSeoPluginBackendDownloadExportProductsController extends shopSeoCsvDownloadExportController
{
	protected function getFilename()
	{
		$date = date('Ymd_His');
		return "export_products_{$date}.csv";
	}
}