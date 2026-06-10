<?php

class shopSeofilterLinkcanonicalCanonical
{
	private $canonical_url_template;
	private $storefront;
	private $category;

	public function __construct($canonical_url_template, $storefront, $category)
	{
		$this->canonical_url_template = $canonical_url_template;
		$this->storefront = $storefront;
		$this->category = $category;
	}

	public function fetchUrl()
	{
		$canonical = $this->canonical_url_template;

		$canonical = $this->tryReplaceCategoryUrlVariable($canonical);
		$canonical = $this->tryReplaceStorefront($canonical);
		$canonical = $this->tryReplaceProto($canonical);

		if (!preg_match('/^http(s?):\/\//', $canonical))
		{
			$canonical = (waRequest::isHttps() ? 'https://' : 'http://') . trim($this->storefront, '/*') . '/' . ltrim($canonical, '/');
		}

		return $canonical;
	}

	private function tryReplaceCategoryUrlVariable($canonical)
	{
		if (strpos($canonical, '{category_url}') === false)
		{
			return $canonical;
		}

		$category_url = waRequest::param('url_type') == 1
			? $this->category['url']
			: $this->category['full_url'];

		return str_replace('{category_url}', trim($category_url, '/'), $canonical);
	}

	private function tryReplaceStorefront($canonical)
	{
		if (strpos($canonical, '{storefront}') === false)
		{
			return $canonical;
		}

		return str_replace('{storefront}', trim($this->storefront, '/*'), $canonical);
	}

	private function tryReplaceProto($canonical)
	{
		if (substr($canonical, 0, strlen('{proto}')) == '{proto}')
		{
			$proto = waRequest::isHttps() ? 'https://' : 'http://';
			//return str_replace('{proto}', $proto, $canonical);
			return $proto . substr($canonical, strlen('{proto}'));
		}
		//elseif (preg_match('/^http(s?):\/\//', $canonical))
		//{
		//	//$proto = waRequest::isHttps() ? 'http://' : 'https://';
		//	//return $proto . $canonical;
		//	return $canonical;
		//}
		else
		{
			return $canonical;
		}
	}
}