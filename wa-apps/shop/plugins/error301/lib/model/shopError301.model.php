<?php
class shopError301Model extends waModel
{
    public $table = 'shop_error301';
	
	public function deleteHistoryByID($ids, $type)
	{
		if($type == 'x')
		{
			foreach($ids as $id)
				$this->exec('DELETE FROM '.$this->table.' WHERE `parent` = ? AND `type` = ?', (int)$id, $type[0]);
		}
		else
		{
			foreach($ids as $id)
				$this->exec('DELETE FROM '.$this->table.' WHERE `id` = ? AND `type` = ?', (int)$id, $type[0]);
		}
	}
	
	public function index()
	{
		$this->exec("INSERT IGNORE INTO `".$this->table."` SELECT `id`, 'c' as `type`, `url`, `parent_id` as `parent` FROM `shop_category`;");
		$this->exec("INSERT IGNORE INTO `".$this->table."` SELECT `id`, 'p' as `type`, `url`, COALESCE(`category_id`, 0) as `parent` FROM `shop_product`;");
		$this->exec("INSERT IGNORE INTO `".$this->table."` SELECT `id`, 'x' as `type`, `url`, `product_id` as `parent` FROM `shop_product_pages`;");
	}
	
	public function getItem($search)
	{
		/*
		$search = array(
			'reviews' => false, //принадлежность адреса к отзыву
			'category' => false, //принадлежность адреса к категории
			'product' => false, //принадлежность адреса к товару
			'productpage' => false, //принадлежность адреса подстранице товара
			'url' => '', //url искомого объекта
			'parents' => array() //список родителей искомого объекта
		);*/
		if(empty($search['url']) && empty($search['parents'])){
			return false;
		}
		
		$query = "SELECT 
		A.`type`,
		A.`id` as `id`,
		A.`parent` as `parentID`,
		CONCAT('/',E.full_url, '/', C.url,'/') as `parent`,
		CONCAT(C.url,'/',B.url,'/') as `url0`,
		CONCAT('product/', C.url,'/',B.url,'/') as `url1`,
		CONCAT(E.full_url, '/', C.url,'/',B.url,'/') as `url2`
		#PARENT1#
		FROM `shop_error301` A
		LEFT JOIN `shop_product_pages` B ON A.id = B.id
		LEFT JOIN `shop_product` C ON B.product_id = C.id
		LEFT JOIN `shop_category_products` D ON C.id = D.product_id
		LEFT JOIN `shop_category` E ON D.category_id = E.id
		WHERE A.`type` = 'x' AND A.`url` = s:url
		UNION
		SELECT 
		A.`type`,
		A.`id` as `id`,
		A.`parent` as `parentID`,
		CONCAT('/', D.full_url,'/') as `parent`,
		CONCAT(B.url,'/') as `url0`,
		CONCAT('product/', B.url,'/') as `url1`,
		CONCAT(D.full_url, '/',B.url,'/') as `url2`
		#PARENT2#
		FROM `shop_error301` A
		LEFT JOIN `shop_product` B ON A.id = B.id
		LEFT JOIN `shop_category_products` C ON B.id = C.product_id
		LEFT JOIN `shop_category` D ON C.category_id = D.id
		WHERE A.`type` = 'p' AND A.`url` = s:url
		UNION
		SELECT 
		A.`type`,
		A.`id` as `id`,
		A.`parent` as `parentID`,
		CONCAT('/', LEFT(B.full_url, LENGTH(B.full_url) - LENGTH(B.url))) as `parent`,
		CONCAT('category/',B.full_url,'/') as `url0`,
		CONCAT('category/',B.url,'/') as `url1`,
		CONCAT(B.full_url,'/') as `url2`
		#PARENT3#
		FROM `shop_error301` A
		LEFT JOIN `shop_category` B ON B.id = A.id
		WHERE A.`type` = 'c' AND A.`url` = s:url
		ORDER BY `rating` DESC;
		";
		
		if(count($search['parents']) == 0)
			$query = str_replace(array("#PARENT1#", "#PARENT2#", "#PARENT3#"),", 0 as `rating`",$query);
		else
		{
			foreach($search['parents'] as &$parent)
			{
				$parent = str_replace("'","",$parent);
				$parentquery[1][] = "POSITION('/".$parent."/' IN CONCAT(' /',E.full_url, '/', C.url,'/'))";
				$parentquery[2][] = "POSITION('/".$parent."/' IN CONCAT('/', D.full_url,'/'))";
				$parentquery[3][] = "POSITION('/".$parent."/' IN CONCAT(' /', LEFT(B.full_url, LENGTH(B.full_url) - LENGTH(B.url))))";
			}
			$query = str_replace(array("#PARENT1#", "#PARENT2#", "#PARENT3#"),array(",(".implode(" + ",$parentquery[1]).") as `rating`", ",(".implode(" + ",$parentquery[2]).") as `rating`", ",(".implode(" + ",$parentquery[3]).") as `rating`"),$query);
		}
		
		$result = $this->query($query, array("url" => $search['url']))->fetchAll();
		foreach($result as $item)
		{
			if($item['type'] == 'x' AND $search['category'] == false)
				return $item;
			if($item['type'] == 'p' AND $search['category'] == false AND $search['productpage'] == false)
				return $item;
			if($item['type'] == 'c' AND $search['product'] == false)
				return $item;
		}
		return false;
	}
}