<?php

class shopBreadcrumbsBlogBreadcrumbsElementCollector
{
	private static $blog = null;
	private static $post = null;
	private static $blog_page = null;

	private $settings;

	private $chain;
	private $element_factory;

	public function __construct(shopBreadcrumbsBlogSettings $settings)
	{
		$this->settings = $settings;

		$this->chain = new shopBreadcrumbsBreadcrumbsChain();
		$this->element_factory = new shopBreadcrumbsBlogBreadcrumbsElementFactory();
	}

	public function getChain()
	{
		return $this->chain;
	}

	public function addHome($home_name)
	{
		$this->chain->add($this->element_factory->home($home_name));

		return $this;
	}

	public function addShopHome($home_name)
	{
		$this->chain->add($this->element_factory->shopHome($home_name));

		return $this;
	}

	public function addBlog()
	{
		$blog = $this->getBlog();
		if ($blog)
		{
			$this->chain->add($this->element_factory->blog($blog));
		}

		return $this;
	}

	public function addBlogAsRoot($element_html)
	{
		$this->chain->add($this->element_factory->blogAsRoot($this->getBlog(), $element_html));

		return $this;
	}

	/**
	 * @return shopBreadcrumbsBlogBreadcrumbsElementCollector
	 */
	public function addBlogPage()
	{
		$this->chain->add($this->element_factory->blogPage($this->getShopPage()));

		return $this;
	}

	/**
	 * @return shopBreadcrumbsBlogBreadcrumbsElementCollector
	 */
	public function addPost()
	{
		$post = $this->getPost();

		if ($post)
		{
			//$this->chain->add($this->element_factory->blog($post['blog_id']));
			$this->chain->add($this->element_factory->post($post));
		}

		return $this;
	}

	private function getBlog()
	{
		if (self::$blog === null)
		{
			self::$blog = wa()->getView()->getVars('blog');

			if (self::$blog === null)
			{
				$blog_id_param = waRequest::param('blog_id');

				self::$blog = wa_is_int($blog_id_param)
					? $blog_id_param
					: false;
			}
		}

		return self::$blog;
	}

	private function getPost()
	{
		if (self::$post === null)
		{
			self::$post = wa()->getView()->getVars('post');
		}

		return self::$post;
	}

	private function getShopPage()
	{
		if (self::$blog_page === null)
		{
			self::$blog_page = wa()->getView()->getVars('page');
		}

		return self::$blog_page;
	}
}