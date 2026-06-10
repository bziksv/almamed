$(function(){
    var setting_fields = "<fieldset class='setting_fields'>" +
                            "<legend>Настройки</legend>" +
                            "<h3>Оригинальный курс по ЦБ на <span class='date'></span></h3>" +
                            "<h3 class='rate'></h3><br/><br/>" +
                            
                            "<div class='setting_div'>" +
                                "<h3>Множитель</h3>" +
                                "<p>$cur_rate - курс валюты на последнюю дату обновления</p>" +
                                "<p>$cur_rate * <input type='text' class='multiple' size='5'>+<input type='text' class='summary' size='5'></p>" +
                            "</div>" +
                            
                            "<div class='setting_div'>" +
                                "<h3>Округление числа</h3>" +
                                "<select name='rounding' class='rounding'>" +
                                    "<option value='1'>до целого числа</option>" +
                                    "<option value='2'>до десятой части</option>" +
                                    "<option value='3'>до сотой части</option>" +
                                "</select>  Шаблон - <span class='rounding_template'></span><br/><br/>"+
                                
                                "<input type='radio' name='rounding_side' class='rounding_side' value='0'>В меньшую сторону<br/>" +
                                "<input type='radio' name='rounding_side' class='rounding_side' value='1'>В большую стороне<br/>" +
                                "<input type='radio' name='rounding_side'  class='rounding_side' value='2'>В ближайшую сторону" +
                            "</div><br/><br/>" +
                            "<input type='button' class='button blue' value='Просмотр' id='preview'><span class='state'></span>" +
                        "</fieldset>",
					 
    close_settings = '<div class="close_settings">' +
                        '<a href="javascript:void(0);" class="button">Скрыть настройки</a>' +
                     '</div>',
    css = {
            display: "inline-block",
            cursor: "pointer",
            "margin-left": "10px"
          };

    $('.update_rate_button').on('click', UpdateRate);
    
    $('#update_all_rate').find('.button').on('click', UpdateAllRates);
    
    //Обработка клика по настройкам					 
    $('#cur_list').find('tr').find('.settings').click(function(){
        var code_data;
        
        $(this).unbind('click');
        code_data = $(this).parent().parent().parent().data('cur');
        showSettings(code_data);
    });

    //Обновить для определённной валюты	 
    function  UpdateRate(){
        var 
            cur = $(this).parent().parent().data('cur'),
            self = $(this),
            data = [],
            update_all_flag;
            
        data.push(cur);
        $(this).parent().append('<i class="icon16 loading"></i>');
        $(this).off('click');
        
        $.post('?plugin=currencyrates&module=update',{code: data},function(response){
            if (response.data.state[cur]) {
                update_all_flag = true; //Флаг для кнопки "Обновить курс всех валют"
                
                //Если курс был не обновлён или на более ранюю дату
                if ($('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+3+")").find('.not_update_rate').length != 0) {
                    $('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+3+")").find('.not_update_rate').remove();
                    $('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+3+")").append("<h5 style='color: green;'>Курс на "+response.data.date+"</h5>");
                }
                
                $('#cur_list').find('tr[data-cur="'+cur+'"]').data('rate', response.data.rate[cur]);
                $('#cur_list').find('tr[data-cur="'+cur+'"]').find('.rate_td').find('.rate').empty().text("1 "+cur+" = "+response.data.tmp_rate[cur]+" RUB");
	
                //Если открыта панель настроек
                if ($('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+1+")").find('.setting_fields').length!=0) {
                    $('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+1+")").find('.setting_fields').find('h3:first').text("Оригинальный курс по ЦБ на "+
                    response.data.date);
                    $('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+1+")").find('.setting_fields').find('h3.rate').text("1 "+cur+" = "+
                    response.data.rate[cur]+" RUB");	 
                }
                
                //Стоит ли быть кнопке "Обновить курс для всех валют"?
                $('#cur_list').find('tr').find("td:eq("+3+")").each(function () {
                    if ($(this).find('.not_update_rate').length !=0) {
                        update_all_flag = false;
                    }
                });
	
                if (update_all_flag) { 
                    $('#update_all_rate').remove();
                }
	
                $('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+3+")").find('.loading').remove();
            } else { 
                alert(response.data.error[cur]);
                $('#cur_list').find('tr[data-cur="'+cur+'"]').find("td:eq("+3+")").find('.loading').remove();
            }
            
            self.on('click',UpdateRate);
        },'json');
    };
 
 
 
    //Обновить курс для всех валют 
    function UpdateAllRates(){
        var 
            data = [],
            self = $(this),
            update_all_flag = true,
            i;
        
        $(this).parent().append('<i class="icon16 loading"></i>');
        $(this).off('click');
        
        //Собираем коды валют
        $('#cur_list').find('tr').each(function(){
            data.push($(this).data('cur'));
        });

        $.post('?plugin=currencyrates&module=update', {code: data}, function (response) {
            for (i=0; i<data.length; i++) {
                //Если есть курс для этой валюты
                if (response.data.state[data[i]]) {
                    if($('#cur_list').find('tr[data-cur="'+data[i]+'"]').find('td:eq('+3+')').find('.not_update_rate').length !=0) {
                        $('#cur_list').find('tr[data-cur="'+data[i]+'"]').find('td:eq('+3+')').find('.not_update_rate').remove();
                        $('#cur_list').find('tr[data-cur="'+data[i]+'"]').find('td:eq('+3+')').append('<h5 style="color:green;">Курс на '+response.data.date+'</h5>');
                    }
	  
                    //Если панель настроек открыта
                    if ($('#cur_list').find('tr[data-cur="'+data[i]+'"]').find('td:eq('+1+')').find('.setting_fields').length!=0) {
                        $('#cur_list').find('tr[data-cur="'+data[i]+'"]').find('td:eq('+1+')').find('.setting_fields').find('h3:first').text("Оригинальный курс по ЦБ на "+
                        response.data.date);
                        
                        $('#cur_list').find('tr[data-cur="'+data[i]+'"]').find('td:eq('+1+')').find('.setting_fields').find('h3.rate').text('1 '+ data[i]+
                        ' = '+ response.data.rate[data[i]]+' RUB');
                    }
                    
                    $('#cur_list').find('tr[data-cur="'+data[i]+'"]').data('rate', response.data.rate[data[i]]);
                    $('#cur_list').find('tr[data-cur="'+data[i]+'"]').find('.rate_td').find('.rate').empty().text('1 '+data[i]+' = '+
                    response.data.tmp_rate[data[i]]+' RUB');
                    $('#update_all_rate').find('.loading').remove();
                } else {//Если нет курса для этой валюты
                    alert('Для валюты '+data[i]+' у Центробанка нет курса к рублю');
                    $('#update_all_rate').find('.loading').remove();
                }
            }
            //Стоит ли быть кнопке "Обновить курс для всех валют"?
            $('#cur_list').find('tr').find("td:eq("+3+")").each(function () {
                if ($(this).find('.not_update_rate').length != 0) {
                    update_all_flag = false;
                }
            });	
            if (update_all_flag) { 
                $('#update_all_rate').remove();
            }
            self.on('click',UpdateAllRates);   
        },'json');
    };
 
    //Панель настроек 
    function showSettings(code_data) {
        var reverse_flag = false;
        $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').find('.settings').parent().before('<i class="icon16 loading"></i>');
  
        $.post('?plugin=currencyrates&module=getrate', {code:code_data}, function (response) {
            if ($('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').length==0) {
                //Выводим панель настроек
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').append(setting_fields);
                
                //Вставляем дату
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('h3:first').find('.date').text(response.data.date);
                
                //Если даты нет
                if (response.data.date == null) {
                    $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('h3:first').text('Данный курс не является курсом ЦБ');
                } else if(response.data.date != response.data.this_date) {//Если дата прошла
                    $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('h3:first').text('Данный курс на '+response.data.date+' и не обновлялся сегодня с ЦБ');
                }
	
                //Вставляем множитель и коеффициент суммы
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('input.multiple').val(response.data.mul);
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('input.summary').val(response.data.sum);
                
                //Вставляем округление
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('.rounding').find('option[value="'+response.data.rounding+'"]').attr('selected',true);
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('input.rounding_side[value="'+response.data.rounding_side+'"]').attr('checked',true);
                RoundingTemplate(parseInt(response.data.rounding), code_data);
                
                //Обработка выбора округления
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('.rounding').change(function () {
                    RoundingTemplate(parseInt($(this).find(':selected').val()), code_data);	   
                });
	   
                //Обработка клика на просмотре
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('#preview').click(function(){
                    var mul = $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('.multiple').val();
                    var sum = $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('.summary').val();
                    var rounding_side = $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('.rounding_side:checked').val();
                    var str='';
                    $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('.setting_fields').find('.state').text('');
                    
                    if ((dataValidationMul(mul) && dataValidationSum(sum))) {
                        var rate;
                        if ($('#cur_list').find('tr[data-cur="'+code_data+'"]').data('rate')) {
                            rate = parseFloat($('#cur_list').find('tr[data-cur="'+code_data+'"]').data('rate'));
                            rate = rate*parseFloat(mul)+parseFloat(sum);
                        } else {
                            rate = response.data.rate*parseFloat(mul)+parseFloat(sum);
                        }
         
                        if (rounding_side == 0) {
                            rate = RoundingRateFloor(parseInt($(this).parent().find('.rounding').val()),rate);
                        } else if(rounding_side == 1) {
                            rate = RoundingRateCeil(parseInt($(this).parent().find('.rounding').val()),rate);
                        } else if(rounding_side == 2) {
                            rate = RoundingRate(parseInt($(this).parent().find('.rounding').val()),rate);
                        }
	   
                        if ($(this).parent().find('.preview_rate').length ==0) {
                            str='<div class="preview_rate" style="display:inline;"> 1 <span class="cur_cur">'+code_data+'</span><span class = "reverse"> <a href="javascript:void(0);" style="display:inline;"><i class="icon-exchange"></i></a> </span><span class="cur_rate">'+rate+'</span><span class = "prim_cur"> '+response.data.prim_cur+'</span></div>'+
                            '<p><input type="button" class="button green" value="Сохранить" id="save" style="margin-top:10px;"></p>';
                            $(this).after(str);
                        } else {
                            $(this).parent().find('.preview_rate').remove();
                            $(this).parent().find('#save').remove();
                            str = '<div class="preview_rate" style="display:inline;"> 1 <span class="cur_cur">'+code_data+'</span><span class = "reverse"> <a href="javascript:void(0);" style="display:inline;"><i class="icon-exchange"></i></a> </span><span class="cur_rate">'+rate+'</span><span class="prim_cur"> '+response.data.prim_cur+'</span></div>'+
                            "<p><input type='button' class='button green' value='Сохранить' id='save' style='margin-top: 10px;'></p>";
                            $(this).after(str);

                        }
                        
                        //Обработка реверса курса
                        $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('div.preview_rate').find('.reverse').click(function () {
                            if (!reverse_flag) {
                                if (rate != 0) {
                                    $(this).parent().find('.cur_cur').text(response.data.prim_cur);
                                    $(this).parent().find('.cur_rate').text(1/rate);
                                    $(this).parent().find('.prim_cur').text(' '+code_data);
                                    reverse_flag = true;
                                } else {
                                    alert('Нулевое отношение курса!');
                                    return false;
                                }
                            } else {
                                $(this).parent().find('.cur_cur').text(code_data);
                                $(this).parent().find('.cur_rate').text(rate);
                                $(this).parent().find('.prim_cur').text(' '+response.data.prim_cur);
                                reverse_flag= false;
                            }
                        });

                    } else {
                        alert('Некорректный ввод данных!');
                        return false;
                    }
	  
                    //Клик на сохранить
                    $(this).parent().find('#save').click(function(){
                        var rates;
                        var mul = $(this).parent().parent().find('.multiple').val();
                        var sum = $(this).parent().parent().find('.summary').val();
                        var round = $(this).parent().parent().find('.rounding :selected').val();
                        var round_side = $(this).parent().parent().find('.rounding_side:checked').val();
                        if (!reverse_flag) {
                            rates = parseFloat($(this).parent().parent().find('.cur_rate').text());
                        } else {
                            rates = parseFloat($(this).parent().parent().find('.cur_rate').text());
                            rates = Math.pow(rates,-1);
                        }
                        
                        if (rate > 0) {
                            $.post('?plugin=currencyrates&module=updaterate',{code:code_data, rate: rates, multiple: mul, summary:sum, rounding: round, rounding_side: round_side},function(response){
                                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').find('.rate').text('');
                                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').find('.rate').append('1 '+code_data+' = '+rates+' '+response.data.prim_cur);
                                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('.setting_fields').find('.state').text('');
                                if (response.data.state) {
                                    $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('.setting_fields').find('.state').append('<p style="color: green;">Обновление прошло!</p>');
                                } else {
                                    $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('.setting_fields').find('state').append('<p style="color: red;">Обновление не прошло! Обратитесь в службу тех. Поддержки плагина kolmakov.igor@gmail.com»</p>');
                                }
                            },'json');
                        } else {
                            alert('Некорекктный ввод данных! Курс получился отрицательным!');
                        }	  
                    });

                });	
                
                //Вставляем курс
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+1+')').find('.setting_fields').find('.rate').text('1 '+code_data+' = '+response.data.rate+' '+response.data.prim_cur);	
            }
   
            //Удаляем значок загрузки
            $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').find('.loading').remove();
            $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('.settings').parent().remove();
            $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').append($(close_settings).css(css));   
  
            //Клик на скрыть настройки
            $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').find('.close_settings').bind('click', function () {
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('.setting_fields').remove();
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').find('.close_settings').remove();
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').append('<a href="javascript:void(0);" title="Настройка" style="display: inline;" class="button"><span class="settings">Настройки</span></a>');
   
                $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('td:eq('+2+')').find('.settings').click(function(){
                    $(this).unbind('click');
                    showSettings(code_data);
                });
            });
        },'json');
    }

    //Шаблон для округления
    function RoundingTemplate(data, code_data) {
        var
            target_element = $('#cur_list').find('tr[data-cur="'+code_data+'"]').find('.setting_fields').find('.rounding_template');
        switch (data) {
        case 1: target_element.text('ss');
            break;
		 
        case 2: target_element.text('ss,s');
            break;
		 
        case 3: target_element.text('ss,ss');
            break;
		 
        default: target_element.text('');
            break;		 
        }
    }

    //Валидация данных для множителя
    function dataValidationMul(str) {
        var 
            p1 = /^\d+$/,
            p2 = /^\d+\.+\d*$/;
 
        if (p1.test(str) || p2.test(str)) {
            return true;
        } else {
            return false;
        }
    }

    //Валидация данных для суммы
    function dataValidationSum(str) {
        var
            p1 = /^-*\d+$/,
            p2 = /^-*\d+\.+\d*$/;
 
        if (p1.test(str) || p2.test(str)) {
            return true;
        } else {
            return false;
        }
    }

    //Округление курса в ближайшую сторону
    function RoundingRate(state, rate) {
        var result;
        if (state == 1) {
            result = Math.round(rate);
        } else if(state==2) {
            result = Math.round(rate*10)/10;
        } else if(state==3) {
            result = Math.round(rate*100)/100;
        }
        
        return result;
    }

    //Округление курса в меньшую сторону
    function RoundingRateFloor(state, rate) {
        var result;
        
        if (state == 1) {
            result = Math.floor(rate);
        } else if(state==2) {
            result = Math.floor(rate*10)/10;
        } else if(state==3) {
            result = Math.floor(rate*100)/100;
        }
        
        return result;
    }

    //Округление курса в большую сторону
    function RoundingRateCeil(state, rate) {
        var result;
        
        if (state == 1) {
            result = Math.ceil(rate);
        } else if(state==2) {
            result = Math.ceil(rate*10)/10;
        } else if(state==3) {
            result = Math.ceil(rate*100)/100;
        }
        return result;
    }
});