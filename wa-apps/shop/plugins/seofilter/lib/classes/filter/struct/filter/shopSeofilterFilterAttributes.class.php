<?php

class shopSeofilterFilterAttributes
{
	private $id;
	private $seo_name;
	private $url;
	private $full_url;
	private $canonical_url = null;
	private $canonical_url_template = null;

	public function __construct(shopSeofilterFilter $filter)
	{
		foreach ($filter->getAttributes() as $name => $value)
		{
			if (property_exists($this, $name))
			{
				$this->{$name} = $value;
			}
		}
	}

	/** @param string $full_url */
	public function setFullUrl($full_url)
	{
		$this->full_url = $full_url;
	}

	/** @return int */
	public function getId()
	{
		return $this->id;
	}

	/** @return string */
	public function getUrl()
	{
		return $this->url;
	}

	/** @return string */
	public function getSeoName()
	{
		return $this->seo_name;
	}

	/** @return string */
	public function getFullUrl()
	{
		return $this->full_url;
	}

	/** @return string */
	public function getCanonicalUrlTemplate()
	{
		return $this->canonical_url_template;
	}

	/** @param string $canonical_url_template */
	public function setCanonicalUrlTemplate($canonical_url_template)
	{
		$this->canonical_url_template = $canonical_url_template;
	}

	/**
	 * @return null
	 */
	public function getCanonicalUrl()
	{
		return $this->canonical_url;
	}

	/**
	 * @param null $canonical_url
	 */
	public function setCanonicalUrl($canonical_url)
	{
		$this->canonical_url = $canonical_url;
	}
}