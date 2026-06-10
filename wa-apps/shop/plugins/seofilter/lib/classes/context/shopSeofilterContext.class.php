<?php

abstract class shopSeofilterContext
{
	/** @var shopSeofilterViewBuffer */
	private $view_buffer = null;
	private $vars;

	protected $frontend_filter = null;
	protected $currency;

	/** @var shopSeofilterParsedTemplate */
	private $parsed_template = null;

	private $breadcrumbs_updated = false;
	private $assign_performed = false;

	public abstract function getCurrentPageUrl();

	public abstract function prepareContext();
	public abstract function assign(shopSeofilterParsedTemplate $template);

	public function __construct(shopSeofilterFrontendFilter $frontend_filter, $currency)
	{
		$this->view_buffer = new shopSeofilterViewBuffer();
		$this->frontend_filter = $frontend_filter;
		$this->currency = $currency;
	}

	public final function apply()
	{
		if ($this->parsed_template === null)
		{
			$this->prepareContext();
			$this->parsed_template = $this->fetchAll();
		}

		$this->setMetaData();

		if (!$this->breadcrumbs_updated)
		{
			$this->updateBreadcrumbs();
			$this->breadcrumbs_updated = true;
		}

		if (!$this->assign_performed)
		{
			$this->assign($this->parsed_template);
			$this->assign_performed = true;
		}

		$this->clearVars();
	}

	public final function setVars($vars)
	{
		$this->view_buffer->assign($vars);
		$this->vars = $this->view_buffer->getVars();
	}

	public final function clearVars()
	{
		$this->view_buffer->clearAllAssign();
	}

	/**
	 * @return shopSeofilterParsedTemplate
	 */
	public final function fetchAll()
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();
		$templates = $this->frontend_filter->getTemplates();

		$parsed = $this->view_buffer->fetchAll($templates);

		$category_page = waRequest::get('page', 1, waRequest::TYPE_INT);

		if ($settings->append_page_number_is_enabled)
		{
			if ($category_page > 1)
			{
				foreach ($this->getAppendPaginationFields() as $field)
				{
					if (isset($parsed[$field]))
					{
						$parsed[$field] = $parsed[$field] . ' - страница ' . $category_page;
					}
				}
			}
		}

		return new shopSeofilterParsedTemplate($parsed);
	}

	public final function getParsedTemplate()
	{
		return $this->parsed_template;
	}

	public final function getVars()
	{
		return $this->vars;
	}

	protected function fetch($template)
	{
		return $this->view_buffer->fetch($template);
	}

	protected function setOldReplacer(shopSeofilterIReplacer $replacer)
	{
		$this->view_buffer->setOldReplacer($replacer);
	}

	protected function getViewBuffer()
	{
		return $this->view_buffer;
	}

	private function setMetaData()
	{
		$template = $this->parsed_template;
		if (!$template)
		{
			return;
		}

		$response = wa()->getResponse();

		$title = $template->meta_title;
		if (!empty($title))
		{
			$response->setTitle($title);

			if (method_exists($response, 'setOGMeta'))
			{
				$response->setOGMeta('og:title', $template->og_meta_title);
			}
		}

		$keywords = $template->meta_keywords;
		if (!empty($keywords))
		{
			$response->setMeta('keywords', $keywords);
		}

		$description = $template->meta_description;
		if (!empty($description))
		{
			$response->setMeta('description', $description);

			if (method_exists($response, 'setOGMeta'))
			{
				$response->setOGMeta('og:description', $template->og_meta_description);
			}
		}
	}

	protected function updateBreadcrumbs()
	{
	}

	protected final function getAppendPaginationFields()
	{
		return array(
			'meta_title',
			'meta_description',
		);
	}
}