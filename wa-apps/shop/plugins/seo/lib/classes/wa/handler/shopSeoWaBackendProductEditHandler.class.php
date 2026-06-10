<?php


class shopSeoWaBackendProductEditHandler
{
	public function handle($product)
	{
		if (!shopSeoSettings::isEnablePlugin())
		{
			return array();
		}

		if ($product instanceof shopProduct)
		{
			$product = $product->getData();
		}

		$action = new shopSeoPluginProductEditAction();
		$action->setProduct($product);
		$html = $action->display(false);

		return array('basics' => $html);
	}
}