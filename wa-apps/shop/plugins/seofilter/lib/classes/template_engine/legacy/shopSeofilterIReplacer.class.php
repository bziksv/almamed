<?php

interface shopSeofilterIReplacer
{
	public function fetch($template);

	public function toSmarty($template);
}