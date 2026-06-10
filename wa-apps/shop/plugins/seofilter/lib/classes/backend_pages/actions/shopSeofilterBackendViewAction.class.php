<?php

abstract class shopSeofilterBackendViewAction extends waViewAction
{
	protected $left_sidebar = array(
		'pages' => array(),
	);

	protected function preExecute()
	{
		$user_rights = new shopSeofilterUserRights();
		if (!$user_rights->hasRights())
		{
			throw new waException('Доступ запрещен', 403);
		}

		$layout = new shopSeofilterBackendLayout();
		$layout->assign('no_level2', true);
		$this->prepareLeftSidebar();
		$this->setLayout($layout);
		$this->addJs(array(
			'tab.js',
		));

		$this->getResponse()->addJs('wa-content/js/ace/ace.js');

		$plugin = wa('shop')->getPlugin('seofilter');

		$version_asset_version = waSystemConfig::isDebug()
			? time()
			: $plugin->getVersion();

		$this->view->assign(array(
			'plugin_url' => $plugin->getPluginStaticUrl(),
			'plugin_version' => $version_asset_version,
			'wa_plugin_version' => $version_asset_version,
			'template_helper' => $this->getTemplateHelperData(),
		));
	}

	public function display($clear_assign = true)
	{
		$this->view->cache($this->cache_time);
		if ($this->cache_time && $this->isCached())
		{
			return $this->view->fetch($this->getTemplate(), $this->cache_id);
		}
		else
		{
			if (!$this->cache_time && $this->cache_id)
			{
				$this->view->clearCache($this->getTemplate(), $this->cache_id);
			}
			$this->preExecute();
			$this->execute();
			$this->postExecute();
			$result = $this->view->fetch($this->getTemplate(), $this->cache_id);
			if ($clear_assign)
			{
				$this->view->clearAllAssign();
			}

			return $result;
		}
	}

	public function postExecute()
	{
		$left_sidebar = $this->left_sidebar;
		$left_sidebar['pages'] = array_values($this->left_sidebar['pages']);
		$left_sidebar['pages'][] = array(
			'text' => 'Настройки плагина',
			'href' => '?action=plugins#/seofilter',
			'current' => false,
			'icon_class' => 'settings',
			'target' => '_blank',
		);
		$this->view->assign('left_sidebar', $left_sidebar);
	}

	protected function addCss(array $local_css)
	{
		foreach ($local_css as $_css)
		{
			wa()->getResponse()->addCss('plugins/seofilter/css/'.$_css, 'shop');
		}
	}

	protected function addJs(array $local_js)
	{
		foreach ($local_js as $_js)
		{
			wa()->getResponse()->addJs('plugins/seofilter/js/'.$_js, 'shop');
		}
	}

	private function prepareLeftSidebar()
	{
		$this->left_sidebar['pages'] = array(
			'add' => array(
				'text' => 'Добавить фильтр',
				'href' => '?plugin=seofilter&action=create',
				'current' => false,
				'icon_class' => 'add',
			),
			'all' => array(
				'text' => 'Все фильтры',
				'href' => '?plugin=seofilter',
				'current' => false,
				'icon_class' => 'folders',
			),
		);
	}

	private function getTemplateHelperData()
	{
		$variables_meta = new shopSeofilterTemplateVariablesMeta();

		return $variables_meta->getMeta();
	}
}