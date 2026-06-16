(function ($) {
    function syncSliderTab() {
        var $item = $('.slider-topmenu-li');
        if (!$item.length) {
            return;
        }

        var hash = (window.location.hash || '').replace(/^#\/?/, '');
        var query = window.location.search || '';
        var active = query.indexOf('action=plugins') >= 0 && hash.indexOf('slider') === 0;

        $item.toggleClass('selected', active).toggleClass('no-tab', !active);
    }

    function placeSliderTab() {
        var $slider = $('.slider-topmenu-li');
        var $managers = $('.productmanager-topmenu-li');
        if ($slider.length && $managers.length) {
            $slider.insertBefore($managers);
        }
    }

    $(function () {
        placeSliderTab();
        syncSliderTab();

        if (typeof $.History !== 'undefined') {
            $.History.bind(function () {
                placeSliderTab();
                syncSliderTab();
            });
        } else {
            $(window).on('hashchange', function () {
                syncSliderTab();
            });
        }
    });
})(jQuery);
