<?php

class shopXmlPlugin extends shopPlugin
{
	protected $table = 'shop_category';
	protected $left = 'left_key';
	protected $root;

	public function __construct($info)
	{
		parent::__construct($info);
		$this->root = wa()->getConfig()->getPath('root') . DIRECTORY_SEPARATOR;
		self::registerAutoload();
	}

	public static function registerAutoload()
	{
		static $registered = false;
		if ($registered) {
			return;
		}
		$registered = true;

		$base = 'wa-apps/shop/plugins/xml/lib/';
		waAutoload::getInstance()->add(array(
			'shopXmlPluginExportService' => $base . 'classes/shopXmlPluginExportService.class.php',
			'shopXmlPluginBackendRunController' => $base . 'actions/shopXmlPluginBackendRun.controller.php',
			'shopXmlPluginBackendDeleteProfileController' => $base . 'actions/shopXmlPluginBackendDeleteProfile.controller.php',
		));
	}

	public function backendMenuExport()
	{
		return array(
			'core_li' => '<li class="no-tab"><a href="?plugin=xml&action=edit">Экспорт Xml для Битрикс</a></li>',
		);
	}

	/** @return shopXmlPluginExportService */
	public function getExportService()
	{
		return new shopXmlPluginExportService($this);
	}

	public function indexBrand($brands, $path, $percent)
	{
		return $this->runLegacyExport(array(
			'mode' => 'brand',
			'brand_ids' => (array) $brands,
			'percent' => $percent,
		), $path);
	}

	public function indexCat($cat, $path, $percent)
	{
		return $this->runLegacyExport(array(
			'mode' => 'category',
			'category_roots' => (array) $cat,
			'percent' => $percent,
		), $path);
	}

	private function runLegacyExport(array $params, $path)
	{
		$service = $this->getExportService();
		$result = $service->runFullExport($params, $path ?: null);
		return !empty($result['products']) || file_exists(ifset($result, 'xml', ''));
	}

	public function getFullTree($fields = '', $status = true, $select = array(), $parent = false)
	{
		$model = new shopCategoryModel();

		if (!$fields) {
			$fields = 'id, url, left_key, right_key, parent_id, depth, name, count, type, status';
		}

		$del = '';
		if ($select) {
			foreach ($select as $sel) {
				$del .= ' AND id != ' . (int) $sel . ' ';
			}
		}

		$parent_sql = '';
		if ($parent) {
			$parent_sql = ' AND parent_id = 0 ';
		}

		$sql = 'SELECT ' . $fields . ' FROM ' . $this->table
			. ' WHERE 1=1 ' . $del . $parent_sql
			. ' ORDER BY ' . $this->left;

		return $model->query($sql)->fetchAll('id');
	}

	public function create_tree($cats, $parent_id)
	{
		if (!is_array($cats) || !isset($cats[$parent_id])) {
			return null;
		}

		$tree = '<Группы>';
		foreach ($cats[$parent_id] as $cat) {
			$tree .= '<Группа>';
			$tree .= '<Ид>' . $cat['id'] . '</Ид>';
			$tree .= '<БитриксКод>' . str_replace('/', '', $cat['url']) . '</БитриксКод>';
			$tree .= '<ПометкаУдаления>false</ПометкаУдаления>';
			$tree .= '<БитриксАктивность>' . $cat['status'] . '</БитриксАктивность>';
			$tree .= '<Наименование>' . self::cleanText($cat['name']) . '</Наименование>';
			$tree .= $this->create_tree($cats, $cat['id']);
			$tree .= '</Группа>';
		}
		$tree .= '</Группы>';

		return $tree;
	}

	public static function cleanText($text)
	{
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = strip_tags($text);
		$text = str_replace(array('&', '<', '>'), array('and', '', ''), $text);
		return trim($text);
	}
}
