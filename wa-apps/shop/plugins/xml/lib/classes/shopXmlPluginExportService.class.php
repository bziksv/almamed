<?php

class shopXmlPluginExportService
{
	const BATCH_SIZE = 20;
	const LOG_FILE = 'shop/xml_export.log';

	/** @var shopXmlPlugin */
	private $plugin;

	/** @var string */
	private $templates_path;

	public function __construct(shopXmlPlugin $plugin = null)
	{
		$this->plugin = $plugin ?: wa('shop')->getPlugin('xml');
		$this->templates_path = wa()->getAppPath('plugins/xml/templates/', 'shop');
	}

	/**
	 * @param array $params
	 * @return array{product_ids:int[],categories_xml:string,log:string[]}
	 */
	public function prepare(array $params)
	{
		$log = array();
		$mode = ifset($params, 'mode', 'category');

		if ($mode === 'brand') {
			$brand_ids = array_map('intval', (array) ifset($params, 'brand_ids', array()));
			$log[] = $this->formatLog('Сбор товаров по брендам: ' . count($brand_ids) . ' бренд(ов)');
			$product_ids = $this->collectProductIdsByBrands($brand_ids);
			$log[] = $this->formatLog('Найдено товаров: ' . count($product_ids));
			$cat_tree = $this->buildCategoryTreeForProducts($product_ids);
		} else {
			$category_roots = array_map('intval', (array) ifset($params, 'category_roots', array()));
			$log[] = $this->formatLog('Сбор товаров по категориям: ' . count($category_roots) . ' корневых веток');
			$cat_tree = $this->buildCategoryTreeForRoots($category_roots);
			$category_ids = $this->flattenCategoryTreeIds($cat_tree);
			$log[] = $this->formatLog('Категорий в выгрузке: ' . count($category_ids));
			$product_ids = $this->collectProductIdsByCategories($category_ids);
			$log[] = $this->formatLog('Найдено товаров: ' . count($product_ids));
		}

		$categories_xml = $this->plugin->create_tree($cat_tree, 0);

		return array(
			'product_ids' => $product_ids,
			'categories_xml' => $categories_xml,
			'log' => $log,
		);
	}

	public function writeXmlHeader($fd, $categories_xml)
	{
		$view = wa()->getView();
		$view->assign('cat', $categories_xml);
		fwrite($fd, $view->fetch($this->templates_path . 'body_open.html'));
	}

	public function writeXmlFooter($fd)
	{
		fwrite($fd, file_get_contents($this->templates_path . 'body_close.html'));
	}

	/**
	 * @param int[] $product_ids
	 * @param float $percent
	 * @return string
	 */
	public function renderProductsBatch(array $product_ids, $percent)
	{
		if (!$product_ids) {
			return '';
		}

		$collection = new shopProductsCollection('id/' . implode(',', $product_ids));
		$products = $collection->getProducts('*', 0, count($product_ids));
		$products = $this->attachCategoryLinks($products);

		$view = wa()->getView();
		$view->assign('product', $products);
		$view->assign('percent', $percent);

		return $view->fetch($this->templates_path . 'product_items.html');
	}

	/**
	 * @param int[] $product_ids
	 * @param string $zip_path
	 * @return int added files count
	 */
	public function appendImagesToZip(array $product_ids, $zip_path)
	{
		if (!$product_ids || !class_exists('ZipArchive')) {
			return 0;
		}

		$zip = new ZipArchive();
		if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
			throw new waException('Не удалось открыть архив изображений: ' . $zip_path);
		}

		$added = 0;
		foreach ($product_ids as $product_id) {
			$product_id = (int) $product_id;
			if (!$product_id) {
				continue;
			}
			$product = new shopProduct($product_id);
			foreach ($product->getImages('big') as $img) {
				$entry = (int) $img['id'] . '.' . $img['ext'];
				if ($zip->locateName($entry) !== false) {
					continue;
				}
				$path = shopImage::getPath(array(
					'product_id' => $product_id,
					'id' => (int) $img['id'],
					'ext' => $img['ext'],
				));
				if ($path && file_exists($path)) {
					$zip->addFile($path, $entry);
					$added++;
				}
			}
		}
		$zip->close();

		return $added;
	}

	public function publishExport($partial_xml_path, $final_xml_path, $zip_path, $partial_zip_path = null)
	{
		if (!file_exists($partial_xml_path)) {
			throw new waException('Файл экспорта не найден');
		}
		waFiles::copy($partial_xml_path, $final_xml_path);
		if ($partial_zip_path && file_exists($partial_zip_path)) {
			waFiles::copy($partial_zip_path, $zip_path);
		}
	}

	public function getDefaultOutputPaths()
	{
		$root = wa()->getConfig()->getPath('root');
		return array(
			'xml' => $root . DIRECTORY_SEPARATOR . 'import.xml',
			'zip' => $root . DIRECTORY_SEPARATOR . 'import_files.zip',
		);
	}

	public function getWorkPath($process_id)
	{
		$dir = wa()->getDataPath('plugins/xml/export/' . $process_id . '/', true, 'shop', true);
		return array(
			'dir' => $dir,
			'partial_xml' => $dir . 'import.partial.xml',
			'partial_zip' => $dir . 'import_files.partial.zip',
		);
	}

	public function formatLog($message)
	{
		return date('H:i:s') . ' — ' . $message;
	}

	public function log($message)
	{
		waLog::log($message, self::LOG_FILE);
	}

	/**
	 * CLI / legacy synchronous export in batches without HTTP.
	 *
	 * @param array $params
	 * @param string|null $xml_path
	 * @param callable|null $progress function($done, $total, $message)
	 */
	public function runFullExport(array $params, $xml_path = null, $progress = null)
	{
		$paths = $this->getDefaultOutputPaths();
		$final_xml = $xml_path ?: $paths['xml'];
		$final_zip = $paths['zip'];

		$work = $this->getWorkPath('cli_' . date('Ymd_His'));
		$prepared = $this->prepare($params);
		$product_ids = $prepared['product_ids'];
		$percent = (float) ifset($params, 'percent', 0);

		if (file_exists($work['partial_zip'])) {
			@unlink($work['partial_zip']);
		}

		$fd = fopen($work['partial_xml'], 'wb');
		$this->writeXmlHeader($fd, $prepared['categories_xml']);

		$total = count($product_ids);
		$done = 0;
		foreach (array_chunk($product_ids, self::BATCH_SIZE) as $chunk) {
			fwrite($fd, $this->renderProductsBatch($chunk, $percent));
			$this->appendImagesToZip($chunk, $work['partial_zip']);
			$done += count($chunk);
			if ($progress) {
				call_user_func($progress, $done, $total, 'Товары: ' . $done . ' / ' . $total);
			}
		}

		$this->writeXmlFooter($fd);
		fclose($fd);

		$this->publishExport($work['partial_xml'], $final_xml, $final_zip, $work['partial_zip']);

		return array(
			'products' => $total,
			'xml' => $final_xml,
			'zip' => file_exists($final_zip) ? $final_zip : null,
		);
	}

	private function collectProductIdsByCategories(array $category_ids)
	{
		$category_ids = array_values(array_filter(array_map('intval', $category_ids)));
		if (!$category_ids) {
			return array();
		}

		$cp_model = new shopCategoryProductsModel();
		$rows = $cp_model->query(
			'SELECT DISTINCT cp.product_id
				FROM ' . $cp_model->getTableName() . ' AS cp
				INNER JOIN shop_product AS p ON p.id = cp.product_id
				WHERE cp.category_id IN (i:ids) AND p.status = 1
				ORDER BY cp.product_id',
			array('ids' => $category_ids)
		)->fetchAll();

		$ids = array();
		foreach ($rows as $row) {
			$ids[] = (int) $row['product_id'];
		}
		return $ids;
	}

	private function collectProductIdsByBrands(array $brand_ids)
	{
		$brand_ids = array_values(array_filter(array_map('intval', $brand_ids)));
		if (!$brand_ids) {
			return array();
		}

		$feature_id = (int) wa()->getSetting('feature_id', 0, array('shop', 'productbrands'));
		if (!$feature_id) {
			return array();
		}

		$pf_model = new shopProductFeaturesModel();
		$rows = $pf_model->query(
			'SELECT DISTINCT pf.product_id
				FROM ' . $pf_model->getTableName() . ' AS pf
				INNER JOIN shop_product AS p ON p.id = pf.product_id
				WHERE pf.feature_id = i:fid
					AND pf.sku_id IS NULL
					AND pf.feature_value_id IN (i:brand_ids)
					AND p.status = 1
				ORDER BY pf.product_id',
			array(
				'fid' => $feature_id,
				'brand_ids' => $brand_ids,
			)
		)->fetchAll();

		$ids = array();
		foreach ($rows as $row) {
			$ids[] = (int) $row['product_id'];
		}
		return $ids;
	}

	private function buildCategoryTreeForRoots(array $category_roots)
	{
		$categories = $this->plugin->getFullTree('', false, $category_roots);
		$cat_tree = array();
		foreach ($categories as $category) {
			$cat_tree[$category['parent_id']][] = $category;
		}
		return $cat_tree;
	}

	private function buildCategoryTreeForProducts(array $product_ids)
	{
		if (!$product_ids) {
			return array();
		}

		$cp_model = new shopCategoryProductsModel();
		$rows = $cp_model->getByField('product_id', $product_ids, true);
		$category_ids = array();
		foreach ($rows as $row) {
			$category_ids[(int) $row['category_id']] = true;
		}

		$all_ids = array();
		$category_model = new shopCategoryModel();
		foreach (array_keys($category_ids) as $category_id) {
			foreach ($this->collectCategoryWithAncestors($category_id, $category_model) as $id) {
				$all_ids[$id] = true;
			}
		}

		if (!$all_ids) {
			return array();
		}

		$categories = $category_model->getById(array_keys($all_ids));
		$cat_tree = array();
		foreach ($categories as $category) {
			$cat_tree[$category['parent_id']][] = $category;
		}
		return $cat_tree;
	}

	private function collectCategoryWithAncestors($category_id, shopCategoryModel $category_model)
	{
		$ids = array();
		$guard = 0;
		while ($category_id && $guard++ < 50) {
			$ids[] = (int) $category_id;
			$row = $category_model->getById($category_id);
			if (!$row) {
				break;
			}
			$category_id = (int) $row['parent_id'];
		}
		return $ids;
	}

	private function flattenCategoryTreeIds(array $cat_tree)
	{
		$ids = array();
		$walk = function ($parent_id) use (&$walk, &$ids, $cat_tree) {
			if (empty($cat_tree[$parent_id])) {
				return;
			}
			foreach ($cat_tree[$parent_id] as $category) {
				$ids[] = (int) $category['id'];
				$walk($category['id']);
			}
		};
		$walk(0);
		return $ids;
	}

	private function attachCategoryLinks(array $products)
	{
		if (!$products) {
			return $products;
		}
		$ids = array_keys($products);
		$cp_model = new shopCategoryProductsModel();
		$rows = $cp_model->getByField('product_id', $ids, true);
		$by_product = array();
		foreach ($rows as $row) {
			$by_product[(int) $row['product_id']][] = $row;
		}
		foreach ($products as $id => &$product) {
			$product['id_category'] = ifset($by_product, (int) $id, array());
		}
		unset($product);
		return $products;
	}
}
