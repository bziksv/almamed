<?php


class shopSeoPluginBackendDownloadExportCategoriesController extends shopSeoCsvDownloadExportController
{
	protected function getFilename()
	{
		$date = date('Ymd_His');
		return "export_categories_{$date}.csv";
	}
}