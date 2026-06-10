(function ($) {
    $.wholesale_settings = {
        options: {},
        init: function (options) {
            this.options = options;
            this.initButtons();
            this.initRouteSelector();
            this.initScroll();
            return this;
        },
        initScroll: function () {
            $(window).scroll(function () {
                var item = $('.field-group.submit');
                var form_bottom_position = $('#plugins-settings-form').offset().top + $('#plugins-settings-form').height();
                var scroll_bottom = $(this).scrollTop() + $(window).height();
                if (form_bottom_position - scroll_bottom > 120 && !item.hasClass("fixed")) {
                    item.hide();
                    item.addClass("fixed").slideToggle(200);
                } else if (form_bottom_position - scroll_bottom < 100 && item.hasClass("fixed")) {
                    item.removeClass("fixed");
                }
            }).scroll();
        },
        initButtons: function () {
            $('#ibutton-status').iButton({
                labelOn: "Вкл", labelOff: "Выкл"
            }).change(function () {
                var self = $(this);
                var enabled = self.is(':checked');
                if (enabled) {
                    self.closest('.field-group').siblings().show(200);
                } else {
                    self.closest('.field-group').siblings().hide(200);
                }
                var f = $("#plugins-settings-form");
                $.post(f.attr('action'), f.serialize());
            });
            $(document).on('click', '.helper-link', function () {
                $(this).closest('.value').next('.help-content').slideToggle('slow');
                $(this).find('i.icon10').toggleClass('darr-tiny').toggleClass('uarr-tiny');
                return false;
            });
            $(document).keydown(function (e) {
                // ctrl + s
                if (e.ctrlKey && e.keyCode == 83) {
                    $('#plugins-settings-form').submit();
                    return false;
                }
            });

        },
        initRouteSelector: function () {
            var templates = this.options.templates;
            $('#route-selector').change(function () {
                var self = $(this);
                var loading = $('<i class="icon16 loading"></i>');
                $(this).attr('disabled', true);
                $(this).after(loading);
                $('.route-container').find('input,select,textarea').attr('disabled', true);
                $('.route-container').slideUp('slow');
                $.get('?plugin=wholesale&module=settings&action=route&route_hash=' + $(this).val(), function (response) {
                    $('.route-container').html(response);
                    loading.remove();
                    self.removeAttr('disabled');
                    $('.route-container').slideDown('slow');

                    $('.route-container .ibutton:not(.s-ibutton-enabled-field)').iButton({
                        labelOn: "Вкл",
                        labelOff: "Выкл",
                        className: 'mini'
                    });
                    $('input[name="route_settings[status]"]').change(function () {
                        if ($(this).is(':checked')) {
                            $(this).closest('.field-group').siblings().show(200);
                        } else {
                            $(this).closest('.field-group').siblings().hide(200);
                        }
                    });
                    $('.s-ibutton-enabled-field.ibutton').iButton({
                        labelOn: "Вкл",
                        labelOff: "Выкл",
                        className: 'mini'
                    }).change(function () {
                        var self = $(this);
                        var enabled = self.is(':checked');
                        if (enabled) {
                            self.closest('.field').siblings('.field').show(200);
                        } else {
                            self.closest('.field').siblings('.field').hide(200);
                        }
                    });

                    for (var i = 0; i < templates.length; i++) {
                        CodeMirror.fromTextArea(document.getElementById(templates[i].id), {
                            mode: "text/" + templates[i].mode,
                            tabMode: "indent",
                            height: "dynamic",
                            lineWrapping: true,
                            onChange: function (c) {
                                c.save();
                            }
                        });
                    }

                    $('.template-block').hide();
                    $('.edit-template').click(function () {
                        $(this).closest('.field').find('.template-block').slideToggle('slow');
                        return false;
                    });
                });
                return false;
            }).change();
        }
    };
})(jQuery);
