(function ($) {

    $('input').blur(function() {
        var self = $(this);
        var field = self.attr('name');
        var value = self.val();
        var product_id = self.closest('tr').attr('data-id');

        $.get("?module=ajax&action=save", { field: field, value: value,product_id: product_id })
            .done(function(data) {
                console.log(data);
            }, "json");

    });

    $('.delete').click(function () {
        var self = $(this);
        var product_id = self.closest('tr').attr('data-id');
        if(product_id){
            $.get("?module=ajax&action=delete", { product_id: product_id})
                .done(function(data) {
                    if(data.status == "ok"){
                        self.closest('tr').remove();
                    }
                }, "json");
        }
        return false;
    });

})(jQuery);