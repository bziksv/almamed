<?php
/**
 * Retina slider: 2600px desktop cap, regenerate tablet/mobile/webp variants.
 */
wa('shop');
shopSliderResponsiveImages::generateAllSlides(true);
shopSliderResponsiveImages::generateAllWebp(true);
