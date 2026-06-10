$(function () {
    //Реакция на изменение размеров изображений
    $(".image_settings_list").find('td.image_size').find('input').focus(function () {
        var old_data_width = $(this).parent().data('width');
        var old_data_height = $(this).parent().data('height');
        var button = $(this).parent().parent().find('.image_save_button');

        if ($(this).attr('class') == 'width') {
            $(this).off('keyup.button_vis').on('keyup.button_vis', function () {
                if ($(this).val() != old_data_width) {
                    button.css('display', 'inline-block');
                } else if (($(this).val() == old_data_width) && ($(this).parent().find('input.height').val() == old_data_height)) {
                    button.css('display', 'none');
                }
            });
        } else if ($(this).attr('class') == 'height') {
            $(this).off('keyup.button_vis').on('keyup.button_vis', function () {
                if ($(this).val() != old_data_height) {
                    button.css('display', 'inline-block');
                } else if (($(this).val() == old_data_height) && ($(this).parent().find('input.width').val() == old_data_width)) {
                    button.css('display', 'none');
                }
            });
        }
    });

    //Реакция на сохранение
    $(".image_settings_list").find('span.image_save_button').click(function () {
        var reg_str = /^\d+$/;
        var width = $(this).parent().parent().find('td.image_size').find('input.width').val();
        var height = $(this).parent().parent().find('td.image_size').find('input.height').val();

        if (reg_str.test(width) && reg_str.test(height) && (width != 0 || height != 0)) {
            var target = $(this);
            if (target.parent().find('.loading').length == 0) {
                var type = target.parent().data('image_type');
                var result_str = width + 'X' + height;
                target.append('<i class="icon16 loading"></i>');
                $.post('?plugin=wmimageincat&module=saveimagesize', {type: type, str: result_str}, function (response) {
                    if (response.data.size) {
                        target.parent().parent().find('td.image_size').data('width', response.data.size.width).data('height', response.data.size.height);
                        target.find('.loading').remove();
                        target.css('display', 'none');
                    }
                }, 'json');
            }
        } else {
            alert('Некорректный ввод данных!');
            return false;
        }
    });
});