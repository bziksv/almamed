(function ($) {
    $(function () {
        $.shop && !$.shop.carts ? $.shop.carts = {
            init: function () {
                $('#menuTmpl').template('menuTmpl');
                $('#menuItemTmpl').template('menuItemTmpl');
                $('#cartsContentTmpl').template('cartsContentTmpl');

                var id = window.location.hash.replace(/#\/carts\//, '');

                this.reloadMenu(id);
                this.reloadContent({id:id});
                this._initMenu();
                this._initContent();

                $("#carts-plugin-settings").iButton({
                    labelOn : "",
                    labelOff : "",
                    classContainer: 'ibutton-container mini'
                }).change(function(){
                    var $f = $(this).closest('form');
                    $.post($f.prop('action'), $f.serialize(), function (r) {
                        if(r.status != 'ok') {
                            alert(r.errors);
                        }
                    }, 'json')
                });

                $("#carts-plugin-settings").find('.name span').each(function() {
                    var el = $(this);
                    var title = el.attr('title');
                    if (title && title != '') {
                        el.attr('title', '').append('<span>' + title + '</span>');
                        var width = el.find('span').width();
                        var height = el.find('span').height();
                        el.hover(
                            function() {
                                el.find('span')
                                    .clearQueue()
                                    .delay(200)
                                    .animate({width: width + 20, height: height + 20}, 200).show(200)
                                    .animate({width: width, height: height}, 200);
                            },
                            function() {
                                el.find('span')
                                    .animate({width: width + 20, height: height + 20}, 150)
                                    .animate({width: 'hide', height: 'hide'}, 150);
                            }
                        ).mouseleave(function() {
                                if (el.children().is(':hidden')) el.find('span').clearQueue();
                            });
                    }
                })
            },
            reloadMenu: function (id) {
                $.get('?plugin=carts&action=menu', function (data) {
                    $('#carts-menu').html($.tmpl('menuTmpl', data, {id: id}));
                    if (!data.data.length) {
                        $('#carts-not-send').show();
                    } else {
                        $('#carts-not-send').hide();
                    }
                }, 'json');
                window.location.hash = '#/carts/'+(id?id:'');
            },
            reloadContent: function (data, del) {
                if (del) {
                    data.del = 1;
                }
                $.post('?plugin=carts&action=message', data, function (res) {
                    $('#carts-content').html($.tmpl('cartsContentTmpl', res.data));
                    CodeMirror.fromTextArea(document.getElementById("carts-email-body"), { mode: "text/html", tabMode: "indent", height: "dynamic", lineWrapping: true });
                    CodeMirror.fromTextArea(document.getElementById("carts-sms-body"), { mode: "text/html", tabMode: "indent", height: "dynamic", lineWrapping: true });
                    $('#send-test-button').click(function () {
                        $.get('?plugin=carts&action=test', {id : res.data.id}, function (r) {
                            $(r).waDialog({
                                onSubmit: function (d) {
                                    var t = $(this);
                                    $('.test-message-send-buttons', t).find('.icon16').show();
                                    $.post('?plugin=carts&action=testSend&id='+res.data.id, t.serialize(), function (testres) {
                                        $('.test-message, .test-message-send-buttons', t).hide();
                                        var teststatus = $('.test-status', t).show();
                                        $('.status', teststatus).addClass(testres.data.status ? 'yes' : 'no');
                                        $('.sent', teststatus).html(testres.data.sent);
                                        $('.comment', teststatus).html(testres.data.comment);

                                    }, 'json');
                                    return false;
                                }
                            });
                        })
                    });
                    $.shop.carts.reloadMenu(res.data.id);
                }, 'json');
            },

            _initMenu: function () {
                var carts_menu = $('#carts-menu');
                $(carts_menu).on('click', 'a', function (e) {
                    $('li',carts_menu).removeClass('selected');
                    var item = $(this);
                    item.parent().addClass('selected');
                    $.shop.carts.reloadContent({id: item.attr('href').replace(/#\/carts\//, '')});
                    e.preventDefault();
                });
            },

            _initContent: function () {
                var cc = $('#carts-content');
                cc.on('click', "#carts-help", function (e) {
                    $("#carts-help-content").toggle(200);
                    e.preventDefault();
                    return false;
                }).on('submit', '#carts-settings-form', function () {
                    var f = $(this);
                    $.shop.carts.reloadContent(f.serialize());
                    return false;
                }).on('change', 'input[name="shop_carts[type]"]', function () {
                    var email = $('#carts-email', cc);
                    var sms = $('#carts-sms', cc);
                    if ($(this).val() == 0) {
                        sms.hide();
                    } else {
                        sms.show();
                    }
                    if ($(this).val() == 1) {
                        email.hide();
                    } else {
                        email.show();
                    }
                });
            }
        } : 0;
    })
})(jQuery);