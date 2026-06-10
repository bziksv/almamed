<?php


class shopSeofilterViewBuffer
{
	protected $view;

	public function __construct()
	{
		$this->view = new waSmarty3View(wa(), array(
			'compile_id' => 'seofilter',
		));

		$smarty_modifiers = new shopSeofilterSmartyModifiers;
		$smarty_modifiers->registerModifiers($this->view->smarty);

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

	public function fetch($template)
	{
		if (strpos($template, '{') === false)
		{
			return $template;
		}

		try
		{
			return $this->view->fetch('string:' . $template);
		}
		catch (SmartyCompilerException  $e)
		{
			return '(!) ' . $template;
		}
	}

	public function fetchAll(array $templates)
	{
		foreach ($templates as $i => $template)
		{
			$templates[$i] = $this->fetch($template);
		}

		return $templates;
	}

	public function clearAllAssign()
	{
		$this->view->clearAllAssign();
	}

	public function setOldReplacer(shopSeofilterIReplacer $replacer)
	{
		$lazy_replacer = new shopSeofilterLazyReplacer($replacer);
		$this->view->smarty->registerFilter('pre', array($lazy_replacer, 'preCompile'));
		$this->view->smarty->registerFilter('output', array($lazy_replacer, 'parseTemplate'));
	}
}