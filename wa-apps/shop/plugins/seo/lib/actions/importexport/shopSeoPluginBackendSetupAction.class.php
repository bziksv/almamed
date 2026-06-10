<?php


class shopSeoPluginBackendSetupAction extends waViewAction
{
	private $group_storefront_service;
	
	public function __construct($params = null)
	{
		parent::__construct($params);
		$this->group_storefront_service = shopSeoContext::getInstance()->getGroupStorefrontService();
	}
	
	public function execute()
	{
		$plugin = wa('shop')->getPlugin('seo');
		$groups_storefronts = $this->getGroupsStorefronts();
		$encoding = $this->getEncoding();
		$delimiters = $this->getDelimiters();
		
		wa()->getView()->assign('groups_storefronts', $groups_storefronts);
		wa()->getView()->assign('encoding', $encoding);
		wa()->getView()->assign('delimiters', $delimiters);
		wa()->getView()->assign('version', $plugin->getVersion());
	}
	
	private function getGroupsStorefronts()
	{
		$groups_storefronts = $this->group_storefront_service->getAll();
		$result = array();
		
		foreach ($groups_storefronts as $group_storefront)
		{
			$result[] = array(
				'id' => $group_storefront->getId(),
				'name' => $group_storefront->getName(),
			);
		}
		
		return $result;
	}
	
	private function getEncoding()
	{
		$encoding = array_diff(mb_list_encodings(), array(
			'pass',
			'wchar',
			'byte2be',
			'byte2le',
			'byte4be',
			'byte4le',
			'BASE64',
			'UUENCODE',
			'HTML-ENTITIES',
			'Quoted-Printable',
			'7bit',
			'8bit',
			'auto',
		));
		
		$popular = array_intersect(array('UTF-8', 'Windows-1251', 'ISO-8859-1',), $encoding);
		
		asort($encoding);
		
		return array_values(array_unique(array_merge($popular, $encoding)));
	}
	
	private function getDelimiters()
	{
		return array(
			array('value' => ';', 'name' => 'Точка с запятой (;)'),
			array('value' => ',', 'name' => 'Запятая (,)'),
			array('value' => 'tab', 'name' => 'Табуляция')
		);
	}
}