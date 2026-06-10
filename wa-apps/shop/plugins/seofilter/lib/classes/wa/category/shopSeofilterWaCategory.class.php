<?php

class shopSeofilterWaCategory implements ArrayAccess
{
	public $id;
	public $left_key;
	public $right_key;
	public $depth;
	public $parent_id;
	public $name;
	public $meta_title;
	public $meta_keywords;
	public $meta_description;
	public $type;
	public $url;
	public $full_url;
	public $count;
	public $description;
	public $conditions;
	public $create_datetime;
	public $edit_datetime;
	public $sort_products;
	public $include_sub_categories;
	public $filter;
	public $status;

	public $smartfilters;

	private $assoc;

	public function __construct($assoc)
	{
		if (!is_array($assoc))
		{
			throw new waException();
		}

		$this->id = $assoc['id'];
		$this->left_key = $assoc['left_key'];
		$this->right_key = $assoc['right_key'];
		$this->depth = $assoc['depth'];
		$this->parent_id = $assoc['parent_id'];
		$this->name = $assoc['name'];
		$this->meta_title = $assoc['meta_title'];
		$this->meta_keywords = $assoc['meta_keywords'];
		$this->meta_description = $assoc['meta_description'];
		$this->type = $assoc['type'];
		$this->url = $assoc['url'];
		$this->full_url = $assoc['full_url'];
		$this->count = $assoc['count'];
		$this->description = $assoc['description'];
		$this->conditions = $assoc['conditions'];
		$this->create_datetime = $assoc['create_datetime'];
		$this->edit_datetime = $assoc['edit_datetime'];
		$this->sort_products = $assoc['sort_products'];
		$this->include_sub_categories = $assoc['include_sub_categories'];
		$this->filter = $assoc['filter'];
		$this->status = $assoc['status'];

		$this->smartfilters = array_key_exists('smartfilters', $assoc) ? $assoc['smartfilters'] : '';

		$this->assoc = $assoc;
	}

	public function getAssoc()
	{
		return $this->assoc;
	}

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->assoc);
	}

	public function offsetGet($offset)
	{
		return $this->assoc[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->assoc[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->assoc[$offset]);
	}
}
