(function($) {
    function productBrandInit () {
        $.products.brandsAction = function () {
            this.load('?plugin=productbrands', function () {
                $("#s-sidebar li.selected").removeClass('selected');
                $("#s-productbrands").addClass('selected');
            });
        }
        $.products.brandAction = function (id, action) {
            var url = '?plugin=productbrands'
            if (action === 'pages') {
                url += '&module=pages'
            } else {
                url += '&action=edit'
            }
            this.load(url + '&id=' + id, function () {
                if (!$("#s-productbrands").hasClass('selected')) {
                    $("#s-sidebar li.selected").removeClass('selected');
                    $("#s-productbrands").addClass('selected');
                }
            });
        }
    }
    if ($.products) {
        productBrandInit()
    } else {
        $(function() {
            productBrandInit();
        });
        // window.addEventListener("load", function () {
        //
        // });
    }
})(jQuery);
