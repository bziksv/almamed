/**
 * Created by Echo-company on 11.09.2016.
 */
var directedit__echocompany;

(function ($) {

    function DirectEditEchoCompany() {
        var that = this;

        //Language
        that.lang = {
            'look':'Preview',
        }

        //Задействовать списки товаров
        that.use_product_list = true;

        //Задействовать Товары -> Отзывы
        that.use_jots = true;

        //Задействовать Товары -> Склады
        that.use_stocks = true;

        //Скролировать при возврате
        that.use_scroll = true;

        //Возврат по умолчанию
        that.return_link = false;

        //Проскролить то товара
        that.need_to_scroll = false;

        //Делаем ссылкой на редактирование
        that.add_edit = function (){

            var obj = $(this);
            
            //Уже изменили ссылки
            if (obj.data('directedit__swaped')==1){
                return;
            }

            //href объекта
            var href = obj.attr('href');

            //Проверяем что ссылка на продукты и нет редактирования
            if ((href.substr(0, 10) == '#/product/')) {

                //Подсветка элемента при нажатие
                if (that.use_scroll) {
                    $(obj).on('click', function () {
                        $('.directedit__active').removeClass('directedit__active');
                        $(this).closest('.product').addClass('directedit__active');
                    })
                }

                //Если сссылка на просмотр
                if (href.indexOf('/edit')==-1){
                    //Добавляем слеш если нет
                    if (href.substr(-1)!='/'){
                        href+='/';
                    }
                    href += 'edit/directedit__back='+encodeURIComponent(window.location.hash)+'/';
                }else{
                    //Ссылка на редактирование, просто добавляем return_link
                    if (href.indexOf('=')!=-1) {
                        //Ссылки на редактирования skus
                        href += '&directedit__back=' + encodeURIComponent(window.location.hash) + '/';
                    }else{
                        if (href.substr(-1)!='/'){
                            href += '/';
                        }
                        href += 'directedit__back=' + encodeURIComponent(window.location.hash) + '/';
                    }
                }

                $(obj).attr('href', href);

            }
            obj.data('directedit__swaped',1);
        }

        //Делаем из ссылки редактирования, ссылку просмотра
        that.remove_edit = function (){
            var obj = $(this);

            //Уже изменили ссылки
            if (obj.data("directedit__swaped")==1){
                return;
            }

            //Родитель
            var p_obj = obj.parent();

            //Небольшая иконка в просмотре
            if (obj.hasClass("icon10")){
                p_obj.html('<i class="icon10 s-instant-edit search directedit__look"></i> '+that.lang.look);
            }else{
                obj.removeClass("edit").addClass("search");
            }

            //Ссылка
            var href = p_obj.attr("href");

            //Добавляем слеш если нет
            if (href.substr(-1)!='/'){
                href+='/';
            }

            //Проверяем что ссылки на редактирвоание нет
            if (href.substr(-5) == 'edit/') {
                href = href.substr(0,href.length-5)+"directedit__back="+encodeURIComponent(window.location.hash)+"/";
                p_obj.attr("href", href);
                p_obj.attr("title",that.lang.look);
            }

            obj.data("directedit__swaped",1);
        }


        //Обработка окна редактирования товара
        that.init_edit = function(){

            //Получаем параметры из строки запроса
            var path = $.product.parsePath(window.location.href);

            //По умолчанию возврат на текущею выборку
            var return_path = '#/' + $.products.list_hash;
            if (path.params && path.params.directedit__back){
                return_path = decodeURIComponent(path.params.directedit__back);
            }

            //Кнопка назад
            $('#shop-productprofile a.back').data("directedit__back", return_path);

            //Пункты меню редактирования
            $("#s-product-edit-menu > li > a").each(function(){
                var submenu_href = $(this).attr("href");
                if (submenu_href.substr(-1)!="/"){
                    submenu_href+="/";
                }
                $(this).attr("href",submenu_href+'directedit__back='+encodeURIComponent(return_path)+'/');
            });

            //Кнопки и ссылки редактирования из режима просмотра
            $("a.s-product-edit-link").each(function(){
                var edit_href = $(this).attr("href");

                if (edit_href.indexOf("=")!=-1) {
                    //Ссылки на редактирования skus
                    $(this).attr("href", edit_href + '&directedit__back=' + encodeURIComponent(return_path) + '/');
                }else{
                    if (edit_href.substr(-1)!="/"){
                        edit_href+="/";
                    }
                    $(this).attr("href", edit_href + 'directedit__back=' + encodeURIComponent(return_path) + '/');
                }
            })

            //Сохраняем для других плагинов
            that.return_link = return_path;
        }

        //меняем ссылки местами
        that.change_links = function () {

            $("#product-list .s-product-name a,#product-list .s-image a").each(that.add_edit);
            $(".s-instant-edit").each(that.remove_edit);

        }

        that.scroll_to = function(first){

            if (that.need_to_scroll!=false){

                var find = ".product[data-product-id="+that.need_to_scroll+"]";

                var window_height = $(window).height();

                if ($(find).length>0) {

                    $(find).addClass('directedit__active');

                    that.need_to_scroll = false;

                    var find_height = $(find).height();

                    var position = $(find).offset().top + find_height/2 - window_height / 2;

                    if (position>(window_height / 2 - find_height)){

                        $('html, body').stop(true, false).animate({scrollTop: position}, 200).queue(function(e) {
                            $(this).stop(true, false).dequeue();
                        })
                    }

                }else{

                    if ($('.lazyloading-link').length>0) {

                        var position = $('.lazyloading-wrapper').offset().top - window_height/2;

                        if (first) {
                            $('html, body').stop(true, false).animate({scrollTop: position}, 50).queue(function () {
                                $(this).stop(true, false).dequeue();
                            })
                        } else {
                            $('html, body').stop(true, false).animate({scrollTop: position}, 200);
                        }

                        $(window).lazyLoad('force');
                    }else{
                        that.need_to_scroll = false;
                    }
                }
            }
        }

        //Обрабатывать списки товаров
        $(document).on('product_list_init_view',function(){
            if (that.use_product_list) {
                that.change_links();

                if (that.use_scroll) {
                    that.scroll_to(true);
                }

                $("#product-list").on('append_product_list', function () {
                    that.change_links();

                    if (that.use_scroll) {
                        that.scroll_to(false);
                    }
                });
            }
        });


        //Back button
        $(document).on('click','#shop-productprofile a.back',function () {
            var obj = $(this);

            if (obj.data('directedit__back')){
                $(this).attr('href', decodeURIComponent(obj.data('directedit__back')));
            }else{
                $(this).attr('href', '#/' + $.products.list_hash)
            }

            if (that.use_scroll) {
                that.need_to_scroll = $('#shop-productprofile').data('product-id')
            }
            
            that.return_link = false;
        })


        //Reviews and stocks
        $( document ).ajaxSuccess(function( event, xhr, settings ) {

            //Reviews
            if (that.use_jots) {
                if (settings.url.indexOf('module=reviews')!=-1){
                    $('#s-content .s-reviews .image a, #s-content .s-reviews .hint a').each(that.add_edit);

                    //Off need to scroll
                    that.need_to_scroll = false;
                }
            }

            //Stocks
            if (that.use_stocks) {
                if (settings.url.indexOf('module=stocks')!=-1) {

                    //Stock logs
                    $('.s-stocks-log .s-stock-productname-column a').each(that.add_edit);

                    //Stocks edit price
                    $('#s-product-stocks .menu-v a').each(that.add_edit);

                    //Stocks
                    $('#s-product-stocks .s-product a').each(that.add_edit);

                    //Off need to scroll
                    that.need_to_scroll = false;
                }
            }
        });

    }

    //Запоминаем глобальный объект
    directedit__echocompany = new DirectEditEchoCompany();

})(jQuery);