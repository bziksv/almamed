(function ($) {

    $('#s-product-save-button').click(function(){

        var product_id = $('.vendorlink_input_edit input').data('edit_product_id');
        var links = [];
        $('.vendorlink_input_edit').each(function() {
            var link = $(this).find('input').val();
            var link_id = $(this).find('input').data('edit_link_id');
            if (link_id) {
                links.push(
                    {
                        id: link_id,
                        text: link
                    }
                );
            }
            else {
                links.push(
                    {
                        text: link
                    }
                );
            }
        });
        $.post('?plugin=vendorlink&action=save', {
            'product_id': product_id,
            'links': links
        }, function (d) {
            if (d.status === 'ok') {
                console.log('vendorlink ok');
            }
        }, 'json');
    });

    $.Vendorlink = {
        save: function (e) {
            var input = $(e).find('input');
            var product_id = input.data('product_id');
            var links = [];
            $('.vendorlink_input').each(function() {
                var link = $(this).val();
                var link_id = $(this).data('link_id');
                if (link_id) {
                    links.push(
                        {
                            id: link_id,
                            text: link
                        }
                    );
                }
                else {
                    links.push(
                        {
                            text: link
                        }
                    );
                }

            });
            $.post('?plugin=vendorlink&action=save', {
                'product_id': product_id,
                'links': links
            }, function (d) {
                if (d.status === 'ok') {
                    $('#vendorlink_save_button').removeClass('gray').addClass('green');
                    $('#vendorlink_links').html(d.data.links_template);
                }
            }, 'json');
        },
        change: function() {
            $('#vendorlink_save_button').removeClass('green').addClass('gray');
        },
        add: function() {
            var last_input = $('.vendorlink_input:last');
            var input = last_input.clone();
            input.removeAttr('data-link_id');
            input.attr('value', '');
            last_input.after(input);
        },
        add_edit: function() {
            var last_input = $('.vendorlink_input_edit:last');
            var input = last_input.clone();
            input.find('input').removeAttr('data-edit_link_id');
            input.find('input').attr('value', '');
            last_input.after(input);
        }
    }
})(jQuery);

