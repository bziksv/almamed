<?php

class shopBreadcrumbsBlogBreadcrumbs
{
	const ACTION_BLOG = 'default';
	const ACTION_PAGE = 'page';
	const ACTION_POST = 'post';

	/**
	 * @var shopBreadcrumbsBlogSettings
	 */
	private $settings;

	private $home_name;

	public function __construct(shopBreadcrumbsBlogSettings $settings, $home_name)
	{
		$this->settings = $settings;
		$this->home_name = $home_name;
	}

	public function getChain()
	{
		$action = shopBreadcrumbsPlugin::param('action');

		$element_collector = new shopBreadcrumbsBlogBreadcrumbsElementCollector($this->settings);

		$use_shop_as_home = $this->settings->root_element_app == 'shop';

		$blog_home_name = $use_shop_as_home ? 'Блог' : $this->home_name;

		$blog_is_root_element = true;

		if ($use_shop_as_home)
		{
			$element_collector->addShopHome($this->home_name);
			$blog_is_root_element = false;
		}

		if (!$this->homeIsBlog())
		{
			$element_collector->addHome($blog_home_name);
			$blog_is_root_element = false;
		}

		if ($action == self::ACTION_BLOG)
		{
			$blog_is_root_element
				? $element_collector->addBlogAsRoot($this->home_name)
				: $element_collector->addBlog();
		}
		elseif ($action == self::ACTION_POST)
		{
			$blog_is_root_element
				? $element_collector->addBlogAsRoot($this->home_name)
				: $element_collector->addBlog();

			$element_collector->addPost();
		}
		elseif ($action == self::ACTION_PAGE)
		{
			$blog_is_root_element
				? $element_collector->addBlogAsRoot($this->home_name)
				: $element_collector->addBlog();

			$element_collector->addBlogPage();
		}

		return $element_collector->getChain();
	}

	private function homeIsBlog()
	{
		return waRequest::param('blog_url_type') > 0;
	}
}