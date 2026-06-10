(function($){
$(function () {
    $('body').on('change', '.quickorder-value>input[type="text"]', function(){
        var f = $(this).closest('form');
        var data = f.serialize().replace(/fields/g,'customer');
        $.post('/cartssave/', data);
    })
})
})(jQuery);