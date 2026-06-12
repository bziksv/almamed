(function ($) {
    function syncProductmanagerTab() {
        var $item = $('.productmanager-topmenu-li');
        if (!$item.length) {
            return;
        }

        var hash = (window.location.hash || '').replace(/^#\/?/, '');
        var query = window.location.search || '';
        var active = query.indexOf('plugin=productmanager') >= 0
            || (query.indexOf('action=plugins') >= 0 && hash.indexOf('productmanager') === 0);

        $item.toggleClass('selected', active).toggleClass('no-tab', !active);
    }

    $(function () {
        syncProductmanagerTab();

        if (typeof $.History !== 'undefined') {
            $.History.bind(syncProductmanagerTab);
        } else {
            $(window).on('hashchange', syncProductmanagerTab);
        }
    });
})(jQuery);
