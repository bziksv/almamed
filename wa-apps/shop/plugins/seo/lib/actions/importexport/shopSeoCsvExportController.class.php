<?php


abstract class shopSeoCsvExportController extends shopSeoImportexportController
{
	/** @var shopCsvWriter */
	protected $writer;
	
	abstract protected function getMap();
	
	protected function init()
	{
		$path = waSystem::getInstance()->getTempPath("seo/csv_export/{$this->processId}") . "/csv_export.csv";
		$this->writer = new shopCsvWriter(
			$path,
			waRequest::request('delimiter'),
			waRequest::request('encoding')
		);
		$this->writer->setMap($this->getMap());
		$this->data['csv_writer'] = serialize($this->writer);
	}
	
	protected function restore()
	{
		$this->writer = unserialize($this->data['csv_writer']);
	}
	
	protected function write($data)
	{
		$this->writer->write($data);
		$this->data['csv_writer'] = serialize($this->writer);
	}
}