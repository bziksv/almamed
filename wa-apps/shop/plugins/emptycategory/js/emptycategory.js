$(function() {

    $('#wa-plugins-content').on('change', '#selected-emptycategory_params_buy', function () {

        var self = $(this);

        $.post('?plugin=emptycategory&module=save', {
            id : self.data('id'),
            code : self.data('code'),
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

    $('#wa-plugins-content').on('click', '.paging#emptycategory a', function () {

        var page = $(this).data('page');
        $.get('?plugin=emptycategory&module=settings', { page : page }, function (response) {

            $('#wa-plugins-content .double-padded').html(response);
            $('#wa-plugins-content .double-padded').find(`.paging a[data-page="${page}"]`).addClass('selected');
            console.log('empty category load!');
        });
        return false;
    });
});
