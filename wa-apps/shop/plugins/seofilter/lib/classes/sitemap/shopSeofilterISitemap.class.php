<?php

interface shopSeofilterISitemap
{
	const ALL_URLS = 'all_urls';

	/**
	 * @param int $page if set to shopSeofilterISitemap::ALL_URLS returns all urls
	 * @return mixed
	 */
	public function generate($page = 1);

	/**
	 * @return int
	 */
	public function countPages();
}