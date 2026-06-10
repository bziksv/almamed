<?php


class shopSeoWaBackendCategoryDialogHandler
{
	public function handle($category)
	{
		if (!shopSeoSettings::isEnablePlugin())
		{
			return '';
		}

		$action = new shopSeoPluginCategoryDialogAction();
		$action->setCategory($category);

		return $action->display(false);
	}
}