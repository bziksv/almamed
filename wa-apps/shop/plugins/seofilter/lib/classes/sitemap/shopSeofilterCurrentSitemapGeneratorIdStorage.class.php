<?php

class shopSeofilterCurrentSitemapGeneratorIdStorage
{
	private $model;

	public function __construct()
	{
		$this->model = new waAppSettingsModel();
	}

	public function get()
	{
		return $this->model->get('shop.seofilter', 'sitemap_generator_id', null);
	}

	public function store($process_id)
	{
		return $this->model->set('shop.seofilter', 'sitemap_generator_id', $process_id);
	}

	public function clear()
	{
		return $this->model->del('shop.seofilter', 'sitemap_generator_id');
	}
}