<?php


class shopSeoWaResponse implements shopSeoResponse
{
	private $plugin_settings_service;
	private $env;
	
	public function __construct(
		shopSeoPluginSettingsService $plugin_settings_service,
		shopSeoEnv $env
	) {
		$this->plugin_settings_service = $plugin_settings_service;
		$this->env = $env;
	}
	
	public function setMetaTitle($meta_title)
	{
		if ($meta_title == '')
		{
			return;
		}
		
		wa()->getResponse()->setTitle($meta_title);
	}
	
	public function setMetaKeywords($meta_keywords)
	{
		if ($meta_keywords == '')
		{
			return;
		}
		
		wa()->getResponse()->setMeta('keywords', $meta_keywords);
	}
	
	public function setMetaDescription($meta_description)
	{
		if ($meta_description == '')
		{
			return;
		}
		
		wa()->getResponse()->setMeta('description', $meta_description);
	}
	
	public function appendPagination($page)
	{
		if (!$this->plugin_settings_service->getSettings()->page_number_is_enabled || $page <= 1)
		{
			return;
		}
		
		$meta_title = wa()->getResponse()->getTitle();
		$meta_description = wa()->getResponse()->getMeta('description');
		$suffix = " | Страница {$page}";
		$this->setMetaTitle("{$meta_title}{$suffix}");
		$this->setMetaDescription("{$meta_description}{$suffix}");
	}
	
	public function appendSort($sort, $direction)
	{
		if (!$direction)
		{
			$direction = 'asc';
		}
		
		if (!$this->plugin_settings_service->getSettings()->sort_is_enabled || !$sort || !$direction)
		{
			return;
		}
		
		$sort_map = array(
			'name' => array(
				'asc' => 'По алфавиту А-Я',
				'desc' => 'По алфавиту Я-А',
			),
			'price' => array(
				'asc' => 'По возрастанию цены',
				'desc' => 'По убыванию цены',
			),
			'create_datetime' => array(
				'asc' => 'По дате добавления: старые-новые',
				'desc' => 'По дате добавления: новые-старые',
			),
			'total_sales' => array(
				'asc' => 'По количеству продаж: от меньшего к большему',
				'desc' => 'По количеству продаж: от большего к меньшему',
			),
			'rating' => array(
				'asc' => 'По оценке: от меньшей к большей',
				'desc' => 'По оценке: от большей к меньшей',
			),
			'stock' => array(
				'asc' => 'По наличию: от меньшего к большему',
				'desc' => 'По наличию: от большего к меньшему',
			),
		);
		
		if (!isset($sort_map[$sort][$direction]))
		{
			return;
		}
		
		$meta_title = wa()->getResponse()->getTitle();
		$meta_description = wa()->getResponse()->getMeta('description');
		
		$suffix = " | {$sort_map[$sort][$direction]}";
		$this->setMetaTitle("{$meta_title}{$suffix}");
		$this->setMetaDescription("{$meta_description}{$suffix}");
	}
	
	public function setOgTitle($og_title)
	{
		if ($og_title == '')
		{
			return;
		}
		
		if (!$this->env->isSupportOg())
		{
			return;
		}
		
		wa()->getResponse()->setOGMeta('og:title', $og_title);
	}
	
	public function setOgDescription($og_description)
	{
		if ($og_description == '')
		{
			return;
		}
		
		if (!$this->env->isSupportOg())
		{
			return;
		}
		
		wa()->getResponse()->setOGMeta('og:description', $og_description);
	}
}