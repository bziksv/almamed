/**
 * Created by snark on 8/15/15.
 */
(function ($) {
    $.Vendorlink = {
        items: null,
        localization: null,
        init: function () {
//            $('#s-order-items').find()
            var items = $.Vendorlink.items;
            for (var key in items) {
                var tr = $('tr[data-id="' + key + '"]');
                var num = 0;
                var links = '';
                for (var i in items[key].links) {
                    num++;
                    var points = '';
                    if (items[key].links[i]['link'].length > 100) {
                        points = '...';
                    }
                    var link = '' +
                        '<tr class="noprint">' +
                        '<td style="border-bottom: none;"  width="100%" colspan="4">' +
                        $.Vendorlink.localization.vendor + num + ':&nbsp' +
                        '<a target="_blank" href="'+items[key].links[i]['link']+'">'+items[key].links[i]['link'].substring(0, 100) + points +'</a>' +
                        '</td>' +
                        '</tr>';
                    links += link;
                }
                tr.after(links);
            }
        }
    }
})(jQuery);

