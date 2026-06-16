(function ($) {
    $(function () {
        $.QuickEditor = {
            settings: {},
            strings: {},
            tabKey: false,
            init: function (settings, strings) {
                $.QuickEditor.scroolWindowToStartLocation();
                if (typeof settings === 'undefined' || typeof strings === 'undefined') {
                    console.error('QuickEditor error: plugin settings are not found on this page. Check existence of the frontend_footer hook in the current design theme.');
                } else {
                    $.QuickEditor.settings = settings;
                    $.QuickEditor.strings = strings;
                }
                if (settings && typeof settings.page_id !== 'undefined') {
                    $.QuickEditor.addPageEditButton(parseInt(settings.page_id));
                }
                if (settings && settings.quick_access_location !== 'hidden') {
                    $.QuickEditor.addQuickAccessButton();
                }
                $.QuickEditor.relocateFixedLinks();

                $(window).keydown(function (event) {
                    if (event.key === 'Shift') {
                        $.QuickEditor.tabKey = true;
                    }
                });

                $(window).keyup(function (event) {
                    if (event.key === 'Shift') {
                        $.QuickEditor.tabKey = false;
                    }
                });

            },
            scroolWindowToStartLocation: function () {
                //Scrool window to start location
                if (typeof ($.cookie) === 'function') {
                    if ($.cookie("quickeditor_wnd_pos") && $.cookie("quickeditor_wnd_pos") > 0) {
                        var wndScrollTop = parseInt($.cookie("quickeditor_wnd_pos"));
                        window.scrollTo(0, wndScrollTop);
                        $.cookie("quickeditor_wnd_pos", 0);
                    }
                }
            },
            relocateFixedLinks: function () {
                $('.quickeditor-fixed-link').each(function () {
                    var $el = $(this);
                    if ($el.data('quickeditor-moved')) {
                        return;
                    }
                    $el.data('quickeditor-moved', true);
                    $('body').append($el);
                    $el.on('mousedown.quickeditor', function (e) {
                        e.preventDefault();
                    });
                });
            },
            showEditPageDialog: function (id) {
                $.QuickEditor.showEditDialog(id, 'page');
            },
            showEditCategoryDialog: function (id) {
                $.QuickEditor.showEditDialog(id, 'category');
            },
            showEditProductDialog: function (id) {
                $.QuickEditor.showEditDialog(id, 'product');
            },
            showEditDialog: function (id, action) {
                var wndParams = '';
                var url_part = '';
                var tab_id = '';
                var width = screen.width - 200;
                var height = screen.height - 200;
                var left = (screen.width - width) / 2;
                var top = (screen.height - height) / 2;
                if (!$.QuickEditor.settings.tab_window) {
                    wndParams = "scrollbars=yes,toolbar=no,directories=no,top=" + top + ",left=" + left + ",width=" + width + ",height=" + height + ",resizable=yes,status=no";
                }
                switch (action) {
                    case 'page':
                        url_part = 'shop/?action=storefronts#/pages/' + id;
                        break;
                    case 'category':
                        url_part = 'shop/?action=products#/products/category_id=' + id;
                        break;
                    case 'plugins':
                        url_part = 'shop/?action=plugins';
                        break;
                    case 'settings':
                        url_part = 'shop/?action=settings';
                        break;
                    case 'orders':
                        url_part = 'shop/?action=orders#/orders/';
                        break;
                    case 'customers':
                        url_part = 'shop/?action=customers';
                        break;
                    case 'reports':
                        url_part = 'shop/?action=reports';
                        break;
                    case 'products':
                        url_part = 'shop/?action=products#/products/view=table&sort=create_datetime&order=desc';
                        break;
                    case 'storefronts':
                        url_part = 'shop/?action=storefronts';
                        break;
                    default:
                        if ($.QuickEditor.tabKey) {
                            tab_id = $.QuickEditor.settings.open_setting_tab;
                        }
                        url_part = 'shop/?action=products#/product/' + id + ($.QuickEditor.settings.always_edit_page ? '/edit/' + tab_id : '');
                }
                //Open window
                var wa_window = window.open($.QuickEditor.settings.wa_backend_url + url_part, '', wndParams);
                $.QuickEditor.injectButtons(wa_window, action);
            },
            injectButtons: function (wa_window, action) {
                switch (action) {
                    case 'category':
                        if ($.QuickEditor.settings.edit_category_settings) {
                            var loopTimer = setInterval(function () {
                                var isPageLoaded = false;
                                if (!isPageLoaded && $('#s-product-list-settings', wa_window.document).length > 0) {
                                    isPageLoaded = true;
                                    wa_window.shopDialogProductsCategory.staticDialog(wa_window.jQuery.product_list.collection_hash[1], null, 'edit');
                                    var subTimer = setInterval(function () {
                                        var isSubPageLoaded = false;
                                        if (!isSubPageLoaded && $('#s-product-category-dialog .dialog-buttons-gradient', wa_window.document).length > 0) {
                                            isSubPageLoaded = true;
                                            //Inject "Save and update" button
                                            $('#s-product-category-dialog .dialog-buttons-gradient', wa_window.document).append('<input id="quickeditor-save-update" type="button" value="' + $.QuickEditor.strings.str_save_update + '" class="button green" style="margin-right: 10px;">');
                                            wa_window.jQuery('#quickeditor-save-update').unbind().click(function () {
                                                wa_window.jQuery('#s-product-list-create-form').on('submit', function () {
                                                    var f = $(this);
                                                    $.post(f.attr('action'), f.serialize(), function () {
                                                        $.QuickEditor.closeAndReload(wa_window, true);
                                                    });
                                                });
                                                wa_window.jQuery('#s-product-list-create-form').submit();
                                            });
                                            //Inject "Save and close" button
                                            if ($.QuickEditor.settings.save_and_close) {
                                                $('#s-product-category-dialog .dialog-buttons-gradient', wa_window.document).append('<input id="quickeditor-save-close" type="button" value="' + $.QuickEditor.strings.str_save_and_close + '" class="button green">');
                                                wa_window.jQuery('#quickeditor-save-close').click(function () {
                                                    wa_window.jQuery('#s-product-list-create-form').on('submit', function () {
                                                        var f = $(this);
                                                        $.post(f.attr('action'), f.serialize(), function () {
                                                            $.QuickEditor.closeAndReload(wa_window, false);
                                                        });
                                                    });
                                                    wa_window.jQuery('#s-product-list-create-form').submit();
                                                });
                                            }
                                            clearInterval(subTimer);
                                        }
                                    }, 10);
                                    //Stop loop
                                    clearInterval(loopTimer);
                                }
                            }, 10);
                        }
                        break;
                    case 'product':
                        //Loop
                        var loopTimer = setInterval(function () {
                            var isPageLoaded = false;
                            if (!isPageLoaded && $('#s-product-edit-save-panel', wa_window.document).length > 0) {
                                isPageLoaded = true;
                                //Inject css
                                $(wa_window.document.head).append('<link rel="stylesheet" type="text/css" href="' + $.QuickEditor.settings.plugin_static_url + 'css/hide.css">');
                                //Show product descriptions on "Basics" tab
                                if ($.QuickEditor.settings.show_description) {
                                    $('.s-product-form.descriptions', wa_window.document).css({'display': 'block'});
                                    wa_window.jQuery.product.editTabDescriptionsAction();
                                }
                                //Inject "Save and update" button
                                $('#s-product-edit-save-panel .block.bordered-top', wa_window.document).append('<input id="quickeditor-save-update" type="button" value="' + $.QuickEditor.strings.str_save_update + '" class="button green" style="margin-right: 10px;">');
                                wa_window.jQuery('#quickeditor-save-update').click(function () {
                                    wa_window.jQuery.product.saveData('profile', null, function () {
                                        $.QuickEditor.closeAndReload(wa_window, true);
                                    });
                                });
                                //Inject "Save and close" button
                                if ($.QuickEditor.settings.save_and_close) {
                                    $('#s-product-edit-save-panel .block.bordered-top', wa_window.document).append('<input id="quickeditor-save-close" type="button" value="' + $.QuickEditor.strings.str_save_and_close + '" class="button green">');
                                    wa_window.jQuery('#quickeditor-save-close').click(function () {
                                        wa_window.jQuery.product.saveData('profile', null, function () {
                                            $.QuickEditor.closeAndReload(wa_window, false);
                                        });
                                    });
                                }
                                //Stop loop
                                clearInterval(loopTimer);
                            }
                        }, 10);
                        break;
                    case 'page':
                        //Loop
                        var loopTimer = setInterval(function () {
                            var isPageLoaded = false;
                            if (!isPageLoaded && $('.wa-page-save-panel', wa_window.document).length > 0) {
                                isPageLoaded = true;
                                //Inject css
                                $(wa_window.document.head).append('<link rel="stylesheet" type="text/css" href="' + $.QuickEditor.settings.plugin_static_url + 'css/hide.css">');
                                //Inject "Save and update" button
                                $('.wa-page-save-panel', wa_window.document).append('<input id="quickeditor-save-update" type="button" value="' + $.QuickEditor.strings.str_save_update + '" class="button green" style="margin-right: 10px;">');
                                wa_window.jQuery('#quickeditor-save-update').click(function () {
                                    wa_window.jQuery('#wa-page-content').waEditor('sync');
                                    wa_window.jQuery("#wa-editor-status").html("<i class='icon16 loading'></i> Saving...").fadeIn("slow");
                                    wa_window.jQuery.post(wa_window.jQuery("#wa-page-form").attr('action'), wa_window.jQuery("#wa-page-form").serialize(), function (response) {
                                        if (response.status === 'ok') {
                                            $.QuickEditor.closeAndReload(wa_window, true);
                                        }
                                    });
                                });
                                //Inject "Save and close" button
                                if ($.QuickEditor.settings.save_and_close) {
                                    $('.wa-page-save-panel', wa_window.document).append('<input id="quickeditor-save-close" type="button" value="' + $.QuickEditor.strings.str_save_and_close + '" class="button green">');
                                    wa_window.jQuery('#quickeditor-save-close').click(function () {
                                        wa_window.jQuery('#wa-page-content').waEditor('sync');
                                        wa_window.jQuery("#wa-editor-status").html("<i class='icon16 loading'></i> Saving...").fadeIn("slow");

                                        wa_window.jQuery.post(wa_window.jQuery("#wa-page-form").attr('action'), wa_window.jQuery("#wa-page-form").serialize(), function (response) {
                                            if (response.status === 'ok') {
                                                $.QuickEditor.closeAndReload(wa_window, false);
                                            }
                                        });
                                    });
                                }
                                //Stop loop
                                clearInterval(loopTimer);
                            }
                        }, 10);
                        break;
                }
            },
            closeAndReload: function (wa_window, reload) {
                //Save scrool location
                if (typeof ($.cookie) === "function") {
                    $.cookie("quickeditor_wnd_pos", $(window).scrollTop());
                }
                //Close and reload
                wa_window.close();
                if (reload) {
                    location.reload(true);
                }
            },
            addPageEditButton: function (id) {
                var divObj = $('<div></div>');
                divObj.addClass('quickeditor-fixed-link');
                if ($.QuickEditor.settings.page_link_location === 'right') {
                    divObj.addClass('quickeditor-fixed-link-right');
                }
                var iconObj = $('<i></i>');
                iconObj.addClass('icon-quickeditor-list');
                divObj.append(iconObj);
                divObj.attr('title', $.QuickEditor.strings.str_edit_page);
                divObj.click(function () {
                    $.QuickEditor.showEditPageDialog(id);
                });
                $('body').prepend(divObj);
            },
            qaMenuItems: ['settings', 'plugins', 'orders', 'products', 'customers', 'reports', 'storefronts'],
            addQuickAccessButton: function () {
                var divObj = $('<div></div>');
                divObj.addClass('quickeditor-fixed-link');
                divObj.css({'margin-top': '50px'});
                if ($.QuickEditor.settings.quick_access_location === 'right') {
                    divObj.addClass('quickeditor-fixed-link-right');
                }
                var iconObj = $('<i></i>');
                iconObj.addClass('icon-quickeditor-sitemap');
                iconObj.click(function () {
                    $.QuickEditor.showEditDialog(null, 'orders');
                });
                var ulObj = $('<ul></ul>');
                ulObj.addClass('quickeditor-qa-menu');
                if ($.QuickEditor.settings.quick_access_location === 'right') {
                    ulObj.addClass('quickeditor-qamenu-right');
                }
                ulObj = $.QuickEditor.getQaMenuItem(ulObj);
                divObj.append(iconObj);
                divObj.append(ulObj);
                divObj.attr('title', $.QuickEditor.strings.str_quick_access);
                divObj.hover(function () {
                    ulObj.show();
                }, function () {
                    ulObj.hide();
                });
                $('body').prepend(divObj);
            },
            getQaMenuItem: function (ulObj) {
                var prefix = 'icon-quickeditor-qamenu-';
                var liObj = null;
                $.each($.QuickEditor.qaMenuItems, function (index, item) {
                    liObj = $('<li></li>');
                    liObj.append('<i class="' + prefix + item + '"></i>');
                    liObj.append($.QuickEditor.strings['str_' + item]);
                    liObj.click(function () {
                        ulObj.hide();
                        $.QuickEditor.showEditDialog(null, item);
                    });
                    ulObj.append(liObj);
                });
                return ulObj;
            }
        };
    });
})(jQuery);