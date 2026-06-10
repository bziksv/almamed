<?php


abstract class shopSeoCsvDownloadExportController extends waController
{
	abstract protected function getFilename();
	
	public function execute()
	{
		$processId = waRequest::get('processId');
		
		$file = waSystem::getInstance()->getTempPath("seo/csv_export/{$processId}")."/csv_export.csv";
		$filename = $this->getFilename();
		waFiles::readFile($file, $filename);
	}
}