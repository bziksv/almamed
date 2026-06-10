(function ($) {



    $('#organize-menu li[data-castom-action="assign-manager"]').click(function(){

        var products = $.product_list.getSelectedProducts();

        if (!products.count) {
            alert($_('Please select at least one product'));
            return false;
        }

        var d = $('#s-manager');
        var showDialog = function () {
            $('#s-manager').waDialog({
                disableButtonsOnSubmit: true,
                onLoad: function () {
                    $(this).find('.dialog-buttons i.loading').hide();
                },
                onSubmit: function (d) {
                    var form = $(this);
                    form.find('.dialog-buttons i.loading').show();
                    var action = form.attr('action');
                    var id_manager = form.serializeArray().shift().value;
                    var product_id;
                    if(products.product_id){
                        product_id = products.product_id
                    }else{
                        product_id = products.hash
                    }
                    var data = {
                        "manager_id" : id_manager,
                        "product_id" : product_id,
						"count" : products.count
                    };

                    $.post(action, data, function (html) {
                        if(html == 1){
                            d.trigger('close');
                        }
                    });
                    return false;
                }
            });
        };

        if (d.length) {
            d.remove();
        }

        $.post('?plugin=productmanager&module=dialog&action=manager', products, function (html) {
            $('body').append(html);
            showDialog();
        });

        return false;

    });



})(jQuery);