<?php

class shopSeofilterUserRights
{
	const EDIT_RIGHT_NAME = 'seofilter.filter_edit';

	public function updateConfig(waRightConfig $config)
	{
		$config->addItem('seofilter_header', 'SEO-фильтр', 'header', array('cssclass' => 'c-access-subcontrol-header', 'tag' => 'div'));
		$config->addItem(self::EDIT_RIGHT_NAME, 'Редактирование фильтров', 'checkbox', array('cssclass' => 'c-access-subcontrol-item'));
	}

	/**
	 * @param null|waAuthUser $user
	 * @return bool
	 */
	public function hasRights($user = null)
	{
		/** @var waAuthUser $user */
		if (!$user)
		{
			$user = wa()->getUser();
		}

		return $user
			? $user->getRights('shop', self::EDIT_RIGHT_NAME) != 0
			: false;
	}
}