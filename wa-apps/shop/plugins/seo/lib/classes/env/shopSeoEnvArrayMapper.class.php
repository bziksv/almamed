<?php


class shopSeoEnvArrayMapper
{
	public function mapEnv(shopSeoEnv $env)
	{
		return array(
			'is_enabled_regions' => $env->isEnabledRegions(),
			'is_enabled_productbrands' => $env->isEnabledProductbrands(),
			'is_enabled_mylang' => $env->isEnabledMyland(),
			'is_support_og' => $env->isSupportOg(),
		);
	}
}