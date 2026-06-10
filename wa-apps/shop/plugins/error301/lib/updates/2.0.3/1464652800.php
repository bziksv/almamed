<?php
	$path = wa()->getAppPath('plugins/error301', 'shop');
	waFiles::delete($path.'/lib/config/install.php', true);