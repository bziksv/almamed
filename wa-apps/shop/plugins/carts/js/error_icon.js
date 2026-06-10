(function ($) {
    $(function () {
        if (!/\?action=plugins/.test(window.location.href)) {
            var link = $("a[href^='?action=plugins']");
            var indicator = link.find('.indicator.red');
            if (indicator.length) {
                indicator.html(parseInt(indicator.html()) + 1);
            } else {
                link.append('<span class="indicator red">1</span>');
            }
        } else {
            $('a[href^="#/carts/"]', $('#plugin-list, #wa-plugin-list')).append('<span style="position:absolute;top:6px;right:0;" class="indicator red">!</span>');
        }
    });
})(jQuery);