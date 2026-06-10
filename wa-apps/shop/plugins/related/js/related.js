(function ($) {

    $('#add-related').click(function(){

        $.post('?plugin=related&action=get', {'product_id': $(this).data('product_id')},
            function (d) {

                if(d.status == "ok"){

                    $.shop.jsonPost(
                        '?module=product&action=relatedSave&id=' + d.data.product_id,
                        {
                            'type': d.data.type,
                            'product_id': d.data.product_id
                        },
                        function () {

                            window.location.reload();
                        }
                    );
                }
            }, 'json');

        return false;
    });


    $('#related-view').change(function () {

        var product_id = $(this).data('product_id');
        var selected = $(this).val();

        $.post('?plugin=related&action=save', {'product_id': product_id, 'selected' : selected},
            function (d) {

                //console.log(d);
            }, 'json');

    });

    $('#s-product-save-button').click(function () {
        var title = $('input[name="title_related"]');
        title.each(function (index, val) {

            $.post('?plugin=related&action=title', {
                'product_id': $(val).data('product_id'),
                    'type' : $(val).data('type'),
                    'title' : $(val).val()
                },
                function (d) {

                    //console.log(d);
                }, 'json');
        });

    });
})(jQuery);

