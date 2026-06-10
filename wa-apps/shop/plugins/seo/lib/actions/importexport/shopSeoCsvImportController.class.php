<?php


abstract class shopSeoCsvImportController extends shopSeoImportexportController
{
	/** @var shopCsvReader */
	private $reader;
	
	abstract protected function getMap();
	
	protected function init()
	{
		$path = waSystem::getInstance()->getTempPath("seo/csv_import/{$this->processId}") . "/csv_import.csv";
		
		$files = waRequest::file('import_file');
		
		foreach ($files as $file)
		{
			if ($file->error_code != UPLOAD_ERR_OK)
			{
				throw new waException('Can\'t upload file');
			}
			else
			{
				$file->moveTo($path);
			}
		}
		
		$this->reader = new shopCsvReader(
			$path,
			waRequest::post('delimiter'),
			waRequest::post('encoding')
		);
		
		$this->reader->setMap($this->getMap());
		$this->data['csv_reader'] = serialize($this->reader);
	}
	
	protected function restore()
	{
		$this->reader = unserialize($this->data['csv_reader']);
	}
	
	protected function getHeader()
	{
		return $this->reader->header();
	}
	
	protected function read()
	{
		if ($this->reader->next())
		{
			$data = $this->reader->current();
			$this->data['csv_reader'] = serialize($this->reader);
			
			return $data;
		}
		
		return null;
	}
	
	protected function offset()
	{
		return $this->reader->offset();
	}
	
	protected function size()
	{
		return $this->reader->size();
	}
}