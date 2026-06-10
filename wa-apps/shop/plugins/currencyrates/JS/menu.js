$(function(){
    $('.block').find('.cron_block').hide();
 
    $('.block').find('.hand_settings').click(function(){
        $(this).parent().find('.selected').removeClass('selected');
        $(this).addClass('selected');
        $(this).parent().parent().find('.hand_block').show();
        $(this).parent().parent().find('.cron_block').hide();
    });
 
    $('.block').find('.cron_settings').click(function(){
        $(this).parent().find('.selected').removeClass('selected');
        $(this).addClass('selected');
        $(this).parent().parent().find('.hand_block').hide();
        $(this).parent().parent().find('.cron_block').show();
    });
});