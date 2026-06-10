<?php

class shopSeofilterGenerateSitemapCli extends waCliController
{
	public function execute()
	{
		$settings_model = new waAppSettingsModel();
		$settings_model->set('shop.seofilter', 'cron_is_installed', time());

		$current_id_storage = new shopSeofilterCurrentSitemapGeneratorIdStorage();
		$current_id = $current_id_storage->get();
		
		//if (!$current_id && !$this->checkTime())
		//{
		//	return;
		//}
		
		if ($current_id)
		{
			$_POST['processId'] = $current_id;

			$generator_controller = new shopSeofilterPluginSitemapCacheGeneratorController();
		}
		else
		{
			$generator_controller = new shopSeofilterPluginSitemapCacheGeneratorController();

			$generator_controller->run();

			$_POST['processId'] = $current_id_storage->get();
		}


		$generator_controller->run();

		if ($generator_controller->isLost())
		{
			$current_id_storage->clear();
		}
		elseif ($generator_controller->callIsDone())
		{
			$model = new shopSeofilterBasicSettingsModel();

			$model->set('sitemap_cron_rebuild_queue_after', time() + shopSeofilterSitemapCache::CACHE_TTL_CRON);
		}
	}

	private function checkTime()
	{
		$settings = shopSeofilterBasicSettingsModel::getSettings();

		$sitemap_cron_rebuild_queue_after = $settings->sitemap_cron_rebuild_queue_after;

		return $sitemap_cron_rebuild_queue_after == null || $sitemap_cron_rebuild_queue_after < time();
	}
}
