$(function() {

    $('#wa-plugins-content').on('change', '#brand-selected', function () {

        var self = $(this);

        $.post('?plugin=emptybrand&module=save', {
            id : self.data('id'),
            code : self.data('brand_code'),
            value : self.val()
        }, function (response) {

            if(response.status == "ok"){

                var icon = $('<i>').css({
                    position: 'absolute',
                    right: '-15px'
                }).addClass('icon10 yes');

                self.after(icon);
                self.next('i').delay('1000').fadeOut();
            }
        });
    });

    $('#wa-plugins-content').on('click', '.paging#emptybrand a', function () {

        var page = $(this).data('page');
        $.get('?plugin=emptybrand&module=settings', { page : page }, function (response) {

            $('#wa-plugins-content .double-padded').html(response);
            $('#wa-plugins-content .double-padded').find(`.paging a[data-page="${page}"]`).addClass('selected');
            console.log('empty brand load!');
        });
        return false;
    });
});
