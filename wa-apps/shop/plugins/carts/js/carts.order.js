(function($){
$(function () {

    /* @todo в некоторых случаях контакты заменяются и пишет "необходимо заполнить" */


    var $content = $('#s-content');
    if(!/carts_plugin/.test(window.location.hash)) {
        $content.find('.carts-plugin-modal').remove()
        return;
    }

    var $modal = $('<div class="carts-plugin-modal"></div>').css({
        position : 'absolute',
        top : 0,
        left : 0,
        height : $content.height(),
        width : '100%',
        background : 'rgba(255,255,255,.8) url(../../wa-content/img/loading32.gif) 50% 50% no-repeat'

    });

    $content.css({
        position : 'relative'
    }).append($modal);

    
    var fill_order = function(code) {

        if(!$.order_edit || !$.order_edit.options.currency) {
            setTimeout(fill_order, 1000);
            return ;
        }

        var url = '?plugin=carts&module=order&action=getData&code='+code+'&currency=' + $.order_edit.options.currency;
        code && $.getJSON(url, function(resp) {
            
            if(resp.status === 'ok') {
                var index = 1;
                var $ok = $('#order-currency');
                var $add_row = $('#s-orders-add-row');

                $.each(resp.data.products, function(i, r){

                    var product = r.product,
                        row;

                    if (product.sku_id && product.skus[product.sku_id]) {
                        product.skus[product.sku_id].checked = true;
                    }

                    if ($ok.length && !$ok.attr('disabled')) {
                        $('<input type="hidden" name="currency">').val($ok.val()).insertAfter($ok);
                        $ok.attr('disabled', 'disabled');
                    }

                    row = tmpl('template-order', {
                        data: r, options: {
                            index: index++,
                            currency: $.order_edit.options.currency,
                            stocks: $.order_edit.stocks,
                            price_edit: $.order_edit.options.price_edit
                        }
                    });

                    row = $(row);
                    row.find('[name*="quantity"]').val(r.quantity);

                    $add_row.before(row);
                });

                $('#s-order-comment-edit').show();

                if(resp.data.contact) {


                    var contact = resp.data.contact,
                        $customer_fields = $('#s-order-edit-customer'),
                        $customer_inputs = $customer_fields.find(':input'),
                        $autocompete_input = $("#customer-autocomplete");

                    var activate = function(contact_id) {
                        $('#s-customer-id').val(contact_id).attr('disabled', false);
                        $('#s-order-edit-customer').removeClass('s-opaque');
                        $autocompete_input.val('').hide(200);
                    };

                    if (contact.id) {
                        $.get('?action=contactForm&id=' + contact.id, function(html) {
                            $customer_fields.find('.field-group:first').html(html);
                            $customer_inputs = $customer_fields.find(':input');
                            activate(contact.id);
                        });
                    } else {
                        $.each(contact, function (name, value) {
                            var selector = '[name="customer[' + name + ']"]';
                            $customer_inputs.filter(selector).val(value);
                        });
                        if(contact['address.shipping][region']) {
                            $customer_inputs.filter('[name="customer[address.shipping][country]"]').trigger('change');
                            $customer_inputs.filter('[name="customer[address.shipping][region]"]').val(contact['address.shipping][region']);
                        }
                        activate(0);
                    }
                }

                $('#shipping_methods').trigger('change');
                $.order_edit.updateTotal();

                var input = $('<input name="carts_plugin_code" type="hidden">').val(code);

                $('#order-edit-form').append(input);
            }

            
            $content.find('.carts-plugin-modal').remove();
        }).fail(function () {
            $content.find('.carts-plugin-modal').remove();
        });
    };


    var code = window.location.hash.split('carts_plugin=');
    $.getJSON('?plugin=carts&module=order&action=getCurrency&code='+code[1], function(resp) {
        if(resp.status === 'ok') {
            $('#order-currency').val(resp.data.currency).trigger('change');
            fill_order(code[1]);
        } else {
            $content.find('.carts-plugin-modal').remove();
        }
    }).fail(function () {
        $content.find('.carts-plugin-modal').remove();
    });

})
})(jQuery);