<?php
	$path = wa()->getAppPath('plugins/error301', 'shop');
	waFiles::delete($path.'/lib/actions', true);
	waFiles::delete($path.'/templates', true);	