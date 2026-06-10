<?php

class shopSeofilterSeoAssignCaseHandler extends shopSeofilterHookHandler
{
	public function handle()
	{
		return !$this->plugin_routing->isSeofilterPage();
	}

	protected function beforeHandle()
	{
		return $this->settings->is_enabled && $this->params == 'category';
	}

	protected function defaultHandleResult()
	{
		return true;
	}
}