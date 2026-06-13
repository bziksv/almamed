<?php

class shopXmlPluginBackendRunController extends waLongActionController
{
	/** @var shopXmlPluginExportService */
	private $export_service;

	protected function preExecute()
	{
		if (!wa()->getUser()->isAdmin() && !wa()->getUser()->getRights('shop', 'settings')) {
			throw new waRightsException('Access denied');
		}
		$this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->getResponse()->sendHeaders();
	}

	public function execute()
	{
		try {
			parent::execute();
		} catch (waException $ex) {
			echo json_encode(array('error' => $ex->getMessage()), JSON_UNESCAPED_UNICODE);
		}
	}

	protected function preInit()
	{
		shopXmlPlugin::registerAutoload();
		$this->export_service = new shopXmlPluginExportService();
		return true;
	}

	protected function init()
	{
		$this->export_service = new shopXmlPluginExportService();

		$mode = waRequest::post('mode', 'category', waRequest::TYPE_STRING_TRIM);
		$percent = (float) waRequest::post('percent', 0);

		$params = array(
			'mode' => $mode,
			'percent' => $percent,
		);

		if ($mode === 'brand') {
			$params['brand_ids'] = waRequest::post('brand', array(), waRequest::TYPE_ARRAY_INT);
			if (!$params['brand_ids']) {
				throw new waException('Выберите хотя бы один бренд');
			}
		} else {
			$all_cat = waRequest::post('all_cat', array(), waRequest::TYPE_ARRAY_INT);
			$del_cat = waRequest::post('del_cat', array(), waRequest::TYPE_ARRAY_INT);
			if ($del_cat) {
				$all_cat = array_values(array_diff($all_cat, $del_cat));
			}
			if (!$all_cat) {
				throw new waException('Не осталось категорий для экспорта');
			}
			$params['category_roots'] = $all_cat;
		}

		$paths = $this->export_service->getWorkPath($this->processId);
		waFiles::create($paths['dir']);

		$this->data['params'] = $params;
		$this->data['stage'] = 'prepare';
		$this->data['offset'] = 0;
		$this->data['product_ids'] = array();
		$this->data['total'] = 0;
		$this->data['images_added'] = 0;
		$this->data['log'] = array();
		$this->data['started_at'] = time();
		$this->data['paths'] = $paths;
		$this->data['output'] = $this->export_service->getDefaultOutputPaths();

		$this->appendLog('Старт экспорта XML для Bitrix');
	}

	protected function restore()
	{
		shopXmlPlugin::registerAutoload();
		$this->export_service = new shopXmlPluginExportService();
	}

	protected function isDone()
	{
		return $this->data['stage'] === 'done';
	}

	protected function step()
	{
		if ($this->data['stage'] === 'prepare') {
			$this->stepPrepare();
			return true;
		}

		if ($this->data['stage'] === 'products') {
			return $this->stepProducts();
		}

		if ($this->data['stage'] === 'finalize') {
			$this->stepFinalize();
			return true;
		}

		return false;
	}

	private function stepPrepare()
	{
		$prepared = $this->export_service->prepare($this->data['params']);
		foreach ($prepared['log'] as $line) {
			$this->appendLog($line);
		}

		$this->data['product_ids'] = $prepared['product_ids'];
		$this->data['total'] = count($prepared['product_ids']);
		$this->data['offset'] = 0;

		if (file_exists($this->data['paths']['partial_zip'])) {
			@unlink($this->data['paths']['partial_zip']);
		}

		$fd = fopen($this->data['paths']['partial_xml'], 'wb');
		$this->export_service->writeXmlHeader($fd, $prepared['categories_xml']);
		fclose($fd);

		if (!$this->data['total']) {
			$this->appendLog('Предупреждение: товары не найдены, создаётся пустой каталог');
			$this->data['stage'] = 'finalize';
		} else {
			$this->appendLog('Запись товаров пакетами по ' . shopXmlPluginExportService::BATCH_SIZE);
			$this->data['stage'] = 'products';
		}
	}

	private function stepProducts()
	{
		$batch_size = shopXmlPluginExportService::BATCH_SIZE;
		$chunk = array_slice($this->data['product_ids'], $this->data['offset'], $batch_size);
		if (!$chunk) {
			$this->data['stage'] = 'finalize';
			return true;
		}

		$percent = (float) ifset($this->data, 'params', 'percent', 0);
		$html = $this->export_service->renderProductsBatch($chunk, $percent);
		file_put_contents($this->data['paths']['partial_xml'], $html, FILE_APPEND | LOCK_EX);

		try {
			$added = $this->export_service->appendImagesToZip($chunk, $this->data['paths']['partial_zip']);
			$this->data['images_added'] += $added;
		} catch (Exception $e) {
			$this->appendLog('Ошибка архива изображений: ' . $e->getMessage());
			$this->export_service->log('process ' . $this->processId . ': ' . $e->getMessage());
		}

		$this->data['offset'] += count($chunk);
		$from = max(1, $this->data['offset'] - count($chunk) + 1);
		$this->appendLog('Товары ' . $from . '–' . $this->data['offset'] . ' из ' . $this->data['total']);

		if ($this->data['offset'] >= $this->data['total']) {
			$this->data['stage'] = 'finalize';
		}

		return true;
	}

	private function stepFinalize()
	{
		file_put_contents($this->data['paths']['partial_xml'], file_get_contents(
			wa()->getAppPath('plugins/xml/templates/body_close.html', 'shop')
		), FILE_APPEND | LOCK_EX);

		$this->export_service->publishExport(
			$this->data['paths']['partial_xml'],
			$this->data['output']['xml'],
			$this->data['output']['zip'],
			$this->data['paths']['partial_zip']
		);

		$duration = time() - (int) $this->data['started_at'];
		$this->appendLog('Готово: ' . $this->data['total'] . ' товаров, изображений в архиве: ' . (int) $this->data['images_added']);
		$this->appendLog('Файлы: import.xml, import_files.zip (' . $this->formatDuration($duration) . ')');
		$this->export_service->log('process ' . $this->processId . ' finished: products=' . $this->data['total']);

		$this->data['stage'] = 'done';
	}

	protected function finish($filename)
	{
		$this->info();
		return (bool) waRequest::post('cleanup');
	}

	protected function info()
	{
		$total = max(1, (int) ifset($this->data, 'total', 0));
		$offset = (int) ifset($this->data, 'offset', 0);
		if ($this->data['stage'] === 'done' || $this->data['stage'] === 'finalize') {
			$offset = $total;
		}

		$progress = min(100, ($offset / $total) * 100);
		$interval = time() - (int) ifset($this->data, 'started_at', time());

		echo json_encode(array(
			'processId' => $this->processId,
			'ready' => $this->isDone(),
			'progress' => sprintf('%0.3f%%', $progress),
			'stage' => ifset($this->data, 'stage', ''),
			'processed' => $offset,
			'total' => (int) ifset($this->data, 'total', 0),
			'time' => $this->formatDuration($interval),
			'log' => array_slice((array) ifset($this->data, 'log', array()), -30),
			'download_xml' => '/import.xml',
			'download_zip' => '/import_files.zip',
			'report' => $this->isDone() ? $this->buildReport() : '',
		), JSON_UNESCAPED_UNICODE);
	}

	private function buildReport()
	{
		$total = (int) ifset($this->data, 'total', 0);
		$images = (int) ifset($this->data, 'images_added', 0);
		$duration = $this->formatDuration(time() - (int) ifset($this->data, 'started_at', time()));
		return '<div class="successmsg" style="border-radius:8px;padding:16px 18px;line-height:1.6;">'
			. '<i class="icon16 yes"></i> '
			. '<strong>Экспорт завершён</strong><br>'
			. 'Товаров: ' . $total . ' · изображений в архиве: ' . $images . ' · время: ' . $duration . '<br>'
			. '<a href="/import.xml" download class="button" style="margin-top:10px;margin-right:8px;">Скачать import.xml</a>'
			. '<a href="/import_files.zip" download class="button" style="margin-top:10px;margin-right:8px;">Скачать import_files.zip</a>'
			. '<a href="javascript:void(0);" class="close hint" style="margin-left:8px;">Закрыть</a>'
			. '</div>';
	}

	private function appendLog($message)
	{
		if (!isset($this->data['log']) || !is_array($this->data['log'])) {
			$this->data['log'] = array();
		}
		$line = $this->export_service
			? $this->export_service->formatLog($message)
			: date('H:i:s') . ' — ' . $message;
		$this->data['log'][] = $line;
		if ($this->export_service) {
			$this->export_service->log($line);
		}
		if (count($this->data['log']) > 200) {
			$this->data['log'] = array_slice($this->data['log'], -200);
		}
	}

	private function formatDuration($seconds)
	{
		$seconds = max(0, (int) $seconds);
		return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor($seconds / 60) % 60, $seconds % 60);
	}
}
