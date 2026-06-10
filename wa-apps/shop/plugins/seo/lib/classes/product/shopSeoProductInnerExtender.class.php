<?php


class shopSeoProductInnerExtender
{
	private $product_data_collector;
	
	public function __construct(
		shopSeoProductDataCollector $product_collector
	) {
		$this->product_data_collector = $product_collector;
	}
	
	public function extend($storefront, $product)
	{
		$seo_name = $this->product_data_collector->collectSeoName($storefront, $product['id'], $info);
		$fields = $this->product_data_collector->collectFieldsValues($storefront, $product['id'], $info);
		
		if ($seo_name === '')
		{
			$seo_name = $product['name'];
		}
		
		$product['seo_name'] = $seo_name;
		$product['fields'] = $fields;
		$product['format_price'] = shop_currency($product['price']);
		
		if (isset($product['skus']))
		{
			$product['sku'] = $product['skus'][$product['sku_id']]['sku'];
		}
		else
		{
			$product['sku'] = null;
		}
		
		return $product;
	}
}