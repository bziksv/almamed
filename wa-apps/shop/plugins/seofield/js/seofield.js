$(function() {

    $('#wa-plugins-content').on('change', '.seofield_filter', function () {

        var filter = {};
        seofield_filter(filter);

        $.get('?plugin=seofield&module=settings', filter, function (response) {

            $('#wa-plugins-content .double-padded').html(response);
        });
    });

    $('#wa-plugins-content').on('click', '.paging.seofield a', function () {

        var data = {};
        var self = $(this);

        var type = self.parent().data('type');
        var page = self.data('page');
        data[type] = page;
        seofield_filter(data);

        $.get('?plugin=seofield&module=settings', data, function (response) {

            $('#wa-plugins-content .double-padded').html(response);
            $('#wa-plugins-content .double-padded').find(`.paging.seofield[data-type="${type}"] a[data-page="${page}"]`).addClass('selected');
        });
        return false;
    });
});

function seofield_filter(obj) {

    var value = {};
    var select = $('#wa-plugins-content').find('select.seofield_filter');

    select.each(function (li, el) {
        value[$(el).data('type')] = $(el).val();
    });

    return $.extend(obj, value);
}

