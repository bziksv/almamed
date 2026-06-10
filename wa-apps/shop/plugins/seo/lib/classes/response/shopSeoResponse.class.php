<?php


interface shopSeoResponse
{
	public function setMetaTitle($meta_title);
	
	public function setMetaKeywords($meta_keywords);
	
	public function setMetaDescription($meta_description);
	
	public function appendPagination($page);
	
	public function appendSort($sort, $direction);
	
	public function setOgTitle($og_title);
	
	public function setOgDescription($og_description);
}