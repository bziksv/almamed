<?php

class shopBreadcrumbsBlogBreadcrumbsElementFactory
{
	private $blog_model;
	private $blog_post_model;
	private $blog_page_model;

	public function __construct()
	{
		$this->blog_model = new blogBlogModel();
		$this->blog_post_model = new blogPostModel();
		$this->blog_page_model = new blogPageModel();
	}

	public function home($home_name)
	{
		return new shopBreadcrumbsBlogHomeBreadcrumbsElement($home_name);
	}

	public function shopHome($home_name)
	{
		return new shopBreadcrumbsHomeBreadcrumbsElement($home_name);
	}

	public function blog($blog)
	{
		return new shopBreadcrumbsBlogBreadcrumbsElement(
			$this->prepareBlog($blog)
		);
	}

	public function blogAsRoot($blog, $element_html)
	{
		return new shopBreadcrumbsBlogRootBreadcrumbsElement(
			$this->prepareBlog($blog),
			$element_html
		);
	}

	public function post($post)
	{
		return new shopBreadcrumbsBlogPostBreadcrumbsElement(
			$this->preparePost($post)
		);
	}

	public function blogPage($page)
	{
		return new shopBreadcrumbsBlogPageBreadcrumbsElement(
			$this->prepareBlogPage($page)
		);
	}

	private function prepareBlog($blog)
	{return wa_is_int($blog)
		? $this->blog_model->getById($blog)
		: $blog;
	}

	private function preparePost($post)
	{
		return wa_is_int($post)
			? $this->blog_post_model->getById($post)
			: $post;
	}

	private function prepareBlogPage($blog_page)
	{
		return wa_is_int($blog_page)
			? $this->blog_page_model->getById($blog_page)
			: $blog_page;
	}
}