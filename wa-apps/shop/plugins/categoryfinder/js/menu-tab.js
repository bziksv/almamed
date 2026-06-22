(function ($) {
    'use strict';

    function syncCategoryfinderTab() {
        var $item = $('.categoryfinder-topmenu-li');
        if (!$item.length) {
            return;
        }

        var hash = (window.location.hash || '').replace(/^#\/?/, '');
        var query = window.location.search || '';
        var active = query.indexOf('action=plugins') >= 0 && hash.indexOf('categoryfinder') === 0;

        $item.toggleClass('selected', active).toggleClass('no-tab', !active);
    }

    function placeCategoryfinderTab() {
        var $cf = $('.categoryfinder-topmenu-li');
        var $slider = $('.slider-topmenu-li');
        var $managers = $('.productmanager-topmenu-li');

        if (!$cf.length) {
            return;
        }

        if ($slider.length) {
            $cf.insertAfter($slider);
        } else if ($managers.length) {
            $cf.insertBefore($managers);
        }
    }

    $(function () {
        placeCategoryfinderTab();
        syncCategoryfinderTab();

        if (typeof $.History !== 'undefined') {
            $.History.bind(function () {
                placeCategoryfinderTab();
                syncCategoryfinderTab();
            });
        } else {
            $(window).on('hashchange', syncCategoryfinderTab);
        }
    });
})(jQuery);
