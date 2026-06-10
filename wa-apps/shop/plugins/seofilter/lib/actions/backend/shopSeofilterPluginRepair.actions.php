<?php

class shopSeofilterPluginRepairActions extends waJsonActions
{
	protected $response = 'Ok';

	public function defaultAction()
	{
		$this->response = "Available repair actions:\r\n\tclean\r\n\tsitemapQueue\r\n\tfilterHash\r\n\tcheckFeatureValues\r\n\tresetCronSitemap";
	}

	public function cleanAction()
	{
		$cleaner = new shopSeofilterCleaner();
		$cleaner->clean();
	}

	public function filterHashAction()
	{
		$repairer = new shopSeofilterFilterHashRepairer();
		$duplicated_hashes = $repairer->repair();

		if (count($duplicated_hashes))
		{
			$this->response = 'Группы фильтров с одинаковыми хешами:' . PHP_EOL;

			foreach ($duplicated_hashes as $filter_ids)
			{
				$anchors = array_map(array($this, '_wrapInAnchor'), $filter_ids);

				$this->response .= implode(', ', $anchors) . PHP_EOL;
			}
		}
	}

	public function checkFeatureValuesAction()
	{
		$checker = new shopSeofilterFilterFeatureValueChecker();

		$invalid_filter_ids = $checker->getInvalidFilterIds();

		if (count($invalid_filter_ids))
		{
			$this->response = 'Фильтры с удаленными характеристиками/значениями:' . PHP_EOL;

			$anchors = array_map(array($this, '_wrapInAnchor'), $invalid_filter_ids);
			$this->response .= implode(', ', $anchors);
		}
		else
		{
			$this->response = 'Все ок';
		}
	}

	public function sitemapQueueAction()
	{
		$sitemap_cache = new shopSeofilterSitemapCache();
		$sitemap_cache->buildQueue();
	}

	public function addColumnAction()
	{
		$model = new waModel();

		$alter_sql = '
ALTER TABLE `shop_seofilter_sitemap_cache_queue`
	ADD COLUMN `filter_ids_with_single_value` TEXT NOT NULL AFTER `filter_ids`;
';

		$model->exec($alter_sql);
	}
//
//	public function changeTypeAction()
//	{
//		$model = new waModel();
//
//		$sql = '
//ALTER TABLE `shop_seofilter_sitemap_cache_queue`
//	CHANGE COLUMN `filter_ids_with_single_value` `filter_ids_with_single_value` TEXT NULL AFTER `filter_ids`;
//
//';
//
//		$model->exec($sql);
//	}

	public function resetCronSitemapAction()
	{
		$current_id_storage = new shopSeofilterCurrentSitemapGeneratorIdStorage();
		$current_id_storage->clear();

		$cache_queue_model = new shopSeofilterSitemapCacheQueueModel();
		$cache_queue_model->exec('DELETE FROM ' . $cache_queue_model->getTableName());
	}

	public function run($params = null)
	{
		$action = $params;
		if (!$action)
		{
			$action = 'default';
		}
		$this->action = $action;
		$this->preExecute();
		$this->execute($this->action);
		$this->postExecute();

		if ($this->action == $action)
		{
			if (waRequest::isXMLHttpRequest())
			{
				$this->getResponse()->addHeader('Content-type', 'application/json');
			}
			$this->getResponse()->sendHeaders();
			if (!$this->errors)
			{
				echo '<pre>' . $this->response . '</pre>';
			}
			else
			{
				echo '<pre>' . json_encode(array('status' => 'fail', 'errors' => $this->errors)) . '</pre>';
			}
		}
	}

	private function _wrapInAnchor($filter_id)
	{
		return "<a href=\"?plugin=seofilter&action=edit&id={$filter_id}\" target=\"_blank\">" . $filter_id . '</a>';
	}
}