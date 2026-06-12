<?php

class shopWebpimagesPluginGenerateCli extends waCliController
{
    public function execute()
    {
        $force = (bool) waRequest::param(0);

        wa('shop');
        waSystem::getInstance('shop', null, true);

        $progress_every = (int) waRequest::param(1);
        if ($progress_every <= 0) {
            $progress_every = 500;
        }

        $stats = shopWebpimagesHelper::generateAll($force, null, $progress_every);

        echo "Summary: processed={$stats['processed']} created={$stats['created']} skipped={$stats['skipped']}\n";
    }
}
