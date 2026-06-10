$(function(){
    var
        currencies_list = $('#currencyrates_cron_settings_currencies_list'),
        currencies_list_save_button = currencies_list.find('.cron_save_curencies_list_button');
    
    /*
     * Событие генерации строки для cron
    */
    $('.cron_block').find('.cron_gen_button').click(function () {
        var 
            hours = $(this).parent().find('.hours').val(),
            minutes = $(this).parent().find('.minutes').val(),
            str = '';
        
        hours = parseInt(hours);
        minutes = parseInt(minutes);
        
        if ((hours >= 0) && (hours <= 23) && (minutes >= 0)&&(minutes <= 59)) {
            str += minutes + ' ' + hours + ' ' + '* * * php ';
            str += $('.cron_block').find('.cmd_path').text();
            
            if ($(this).parent().parent().find('.cron_result').length == 0) {
                $(this).parent().parent().append('<div class="cron_result"><h3>Строка для CRON</h3><p>' + str + '</p></div>');
            } else {
                $(this).parent().parent().find('.cron_result').text('');
                $(this).parent().parent().find('.cron_result').append('<h3>Строка для CRON</h3><p>' + str + '</p>');
            }   
        } else {
            alert('Некорректный ввод данных');
            return false;
        }
    });
    
    /*
     * Изменение в коллекции выделенных валют
    */
    currencies_list.off('change').on('change', 'input[type="checkbox"]', function () {
        currencies_list_save_button.removeClass('green').addClass('yellow');
        activateCurrenciesListSaveButton();
    });
    
    function activateCurrenciesListSaveButton() {
        var
            selected_currency = [],
            target_elements,
            path = '?plugin=currencyrates&module=saveselectedcurrencies',
            self;
            
        currencies_list_save_button.off('click').on('click', function () {
            self = $(this);
            target_elements = currencies_list.find('input[type="checkbox"]');
            
            self.before('<i class="icon16 loading"></i>');
            
            target_elements.each(function () {
                if ($(this).attr('checked') == 'checked') {
                    selected_currency.push($(this).val());
                }                
            });
            
            $.post(path, {'currency': selected_currency}, function (response) {    
                //alert(response.data.state);
                deactivateCurrenciesListSaveButton();
            }, 'json').complete(function () {
                self.parent().find('.loading').remove();
            });
        });
    }
    
    function deactivateCurrenciesListSaveButton() {
        currencies_list_save_button.removeClass('yellow').addClass('green');
        currencies_list_save_button.off('click');
    }
});