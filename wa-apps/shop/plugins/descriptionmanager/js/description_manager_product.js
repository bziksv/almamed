(function ($) {

    $('body').attr('onbeforeprint', 'descriptionmanager()');

    $.post('/descriptionmanager/get/', {'product_id' : $('input[name="product_id"]').val()}, function (d) {
        $('#cart-flyer').attr('data-print_price', d.data.request.price);
        $('#cart-flyer').attr('data-print_delivery', d.data.request.delivery);
    }, 'json');

    $('#s-product-edit-save-panel').on('click','input.button',function(){

        var textarea = $('#descriptionmanager_edit');

        $.post('?plugin=descriptionmanager&action=save',
            {'product_id': textarea.data('edit_product_id'), 'description': textarea.val()},
            function (d) {

            console.log(d);
        }, 'json');
    });

    $('#descriptionmanager_form_frontend').submit(function () {

        var data = $(this).serializeArray();

        $.post('/descriptionmanager/', data,
            function (d) {
                if (d.status === 'ok') {
                    window.location.reload();
                }
            }, 'json');

        return false;
    });

    $('div.value.s-product-categories input.s-product-categories').each(function (index, value) {

        $(value).closest('.value').find('.s-product-delete-from-category')
            .before(`<a href="/webasyst/shop/?action=products#/products/category_id=${$(value).val()}" target="_blank"> >>> </a>`);
    });

    var $select2 = $('div.value.s-product-categories').filter(function() {
            return $(this).css('display') != 'none';
        }
    ).find('select.s-product-categories');

    $select2.select2({
        width: '70%',
        matcher: matchStart
    });

    $(".s-category-action a.js-action").on("click", function () {
        var $self = $(this);

        setTimeout(function () {

            let select = $self.closest('.field').find('div.value.s-product-categories.no-shift');
            select.last().find('select.s-product-categories').select2({
                matcher: matchStart
            });
            select.last().after(select.eq(select.length - 2));
        }, 500);
    });

})(jQuery);

function matchStart(params, data) {

    if ($.trim(params.term) === '') { return data; }
    if (typeof data.text === 'undefined') { return null; }

    var q = params.term.toLowerCase();
    if (data.text.toLowerCase().indexOf(q) > -1 || data.id.toLowerCase().indexOf(q) > -1) {

        var modifiedData = $.extend({}, data, true);
        modifiedData.text += ` [${modifiedData.id}]`;

        return modifiedData;
    }
    return null;
}

function descriptionmanager() {

    $('.descriptionmanager_delivery').remove();

    if($('#cart-flyer').data('print_price')){

        $('.add2cart .price-wrapper').html('<span class="price nowrap">' + $('#cart-flyer').data('print_price') + '</span>');
    }

    if($('#cart-flyer').data('print_delivery')){

        $('article #overview').append('<div class="descriptionmanager_delivery">\n' +
            '            <div class="h2 product-tabs-nav-trigger-wrapper">\n' +
            '                <a href="#" class="product-tabs-nav-trigger">Срок поставки</a>\n' +
            '            </div>\n' +
            '            <p>'+ $('#cart-flyer').data('print_delivery') +'</p>\n' +
            '        </div>');
    }
}

