<?php

class shopSliderPluginGenerateResponsiveCli extends waCliController
{
    public function execute()
    {
        $force = (bool) waRequest::param(0);

        wa('shop');
        waSystem::getInstance('shop', null, true);

        $stats = shopSliderResponsiveImages::generateAllSlides($force);
        $webp_stats = shopSliderResponsiveImages::generateAllWebp($force);

        echo "Slider responsive banners\n";
        echo "Processed: {$stats['processed']}\n";
        echo "Updated: {$stats['updated']}\n";
        echo "Skipped: {$stats['skipped']}\n";
        echo "WebP processed: {$webp_stats['processed']}\n";
        echo "WebP created: {$webp_stats['created']}\n";
        echo "WebP skipped: {$webp_stats['skipped']}\n";
    }
}
