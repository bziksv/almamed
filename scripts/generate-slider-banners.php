#!/usr/bin/env php
<?php
/**
 * Generate adapted tablet/mobile slider banners from desktop originals.
 * Usage: php scripts/generate-slider-banners.php [--force]
 */

$root = dirname(__DIR__);
require_once $root . '/wa-config/SystemConfig.class.php';

$config = new SystemConfig('cli');
waSystem::getInstance(null, $config);
waSystem::getInstance('shop', null, true);
require_once wa()->getAppPath('plugins/slider/lib/classes/shopSliderResponsiveImages.class.php', 'shop');

$force = in_array('--force', $argv, true);
$stats = shopSliderResponsiveImages::generateAllSlides($force);

echo "Slider responsive banners\n";
echo "Processed: {$stats['processed']}\n";
echo "Updated: {$stats['updated']}\n";
echo "Skipped: {$stats['skipped']}\n";
