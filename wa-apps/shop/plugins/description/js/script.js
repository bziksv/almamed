$(function() {

    var page = 1;

    $('body').keydown(function(eventObject){

        if(eventObject.which == 37){
            if(page > 1){
                page--;
                upload(page);
            }
        }
        if(eventObject.which == 39){
            if(page >= 1){
                page++;
                upload(page);
            }
        }
    });


    $('.pagination-item').click(function(){
        page = parseInt($(this).attr('id'));
        if(page){
            upload(page);
        }
    });


     function upload(page){
         $.post('?plugin=description&module=ajax', {page : page}, function (response) {
             if(response.status == "ok" && response.data.products){
                 $('.data-rows').remove();
                 $('.pagination-item').removeClass('active');
                 $.each(response.data.products, function (index, value) {
                     var td = '<td>'+value.id+'</td>';
                     td += '<td><a href="/category/'+value.cat_parent_url+'/" target="_blank">'+value.cat_parent_name+'</a></td>';
                     td += '<td><a href="/category/'+value.cat_url+'/" target="_blank">'+value.cat_name+'</a></td>';
                     td += '<td><a href="/webasyst/shop/?action=products#/product/'+value.id+'" target="_blank">'+value.name+'</a></td>';
                     td += '<td><a href="/product/'+value.url+'/" target="_blank">/product/'+value.url+'</a></td>';
                     td += '<td><input type="checkbox" onchange="updateProduct($(this).val(),$(this).is(\':checked\'))" value="'+value.id+'"><span></span></td>';

                     $('#loaded_max').append('<tr class="data-rows">'+ td +'</tr>');
                 });
                 $('.pagination-item#'+page).addClass('active');
             }
         });
     }
});






function updateProduct(val,checked){
    if(val){
        $.post('?plugin=description&module=update', {id : parseInt(val),status : checked}, function (response) {
            if(response.data.desc){
                var icon;
                if(checked)
                    icon = "no";
                else
                    icon = "yes";

                $('input[value="'+val+'"]').parent().find('span').html('<i class="icon10 '+icon+'"></i>');
            }
        });
    }
}