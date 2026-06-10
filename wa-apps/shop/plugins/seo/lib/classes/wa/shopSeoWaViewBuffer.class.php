<?php


class shopSeoWaViewBuffer implements shopSeoViewBuffer
{
	protected $view;

	public function __construct()
	{
		$this->view = new waSmarty3View(wa(), array(
			'compile_id' => 'seo',
		));

		$smarty_plugins_dir = wa()->getAppPath('plugins/seo/lib/smarty-plugins', 'shop');
		$this->view->smarty->addPluginsDir($smarty_plugins_dir);
		$this->view->smarty->caching = false;
	}

	public function assign($name, $value = null)
	{
		$this->view->assign($name, $value);
	}

	public function getVars($name = null)
	{
		return $this->view->getVars($name);
	}

	public function render($template)
	{
		if (strpos($template, '{') === false)
		{
			return $template;
		}

		try
		{
			$result = $this->view->fetch('string:' . $template);

			if (strlen($result) === 0)
			{
				return " ";
			}

			return $result;
		}
		catch (SmartyCompilerException  $e)
		{
			return '(!) ' . $template;
		}
	}

	public function renderAll($templates)
	{
		foreach ($templates as $i => $template)
		{
			$templates[$i] = $this->render($template);
		}

		return $templates;
	}

	public function clearAllAssign()
	{
		$this->view->clearAllAssign();
	}
}