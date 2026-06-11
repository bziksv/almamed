<?php

wa('shop');
require_once wa()->getAppPath('plugins/slider/lib/classes/shopSliderResponsiveImages.class.php', 'shop');
shopSliderResponsiveImages::generateAllSlides(true);
