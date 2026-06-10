<?php


interface shopSeoPluginSettingsSource
{
	public function getSettings();
	
	public function updateSettings($rows);
}