<?php

class shopWebpimagesPluginGenerateCli extends waCliController
{
    public function execute()
    {
        $force = (bool) waRequest::param(0);

        wa('shop');
        waSystem::getInstance('shop', null, true);

        $stats = shopWebpimagesHelper::generateAll($force);

        echo "Product thumbs WebP\n";
        echo "Processed: {$stats['processed']}\n";
        echo "Created: {$stats['created']}\n";
        echo "Skipped: {$stats['skipped']}\n";
    }
}
