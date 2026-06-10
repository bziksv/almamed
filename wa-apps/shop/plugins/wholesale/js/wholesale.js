(function ($) {
    $.wholesale = {
        cart: {
            options: {
                cart_actions: [],
                checkout_selector: '',
                is_cart: false,
                is_onestep: false,
                is_ss8order: false,
                onestep_url: '',
                order_calculate_url: '',
                url: '',
                wholesale_selector: '#wholesale-cart'
            },
            init: function (options) {
                $.extend(this.options, options);

                if (!this.checkSettings()) {
                    return false;
                }
                console.log('wholesale init:');
                console.log(this.options);

                if (this.options.is_ss8order) {
                    this.orderCalculate();
                } else if (this.options.is_cart) {
                    this.cartActions();
                } else if (this.options.is_onestep) {
                    this.onestepComplete();
                }
                this.checkCart();
            },
            checkSettings: function () {
                /*Проверка наличия плагина заказ на одной странице*/
                if ($('.onestep-cart').length) {
                    this.options.is_onestep = true;
                } else if ($('#wa-order-form-wrapper').length) {
                    /*Проверка одностраничного оформления заказа в Shop-Script 8*/
                    this.options.is_ss8order = true;
                    this.options.checkout_selector = '.wa-actions-section .js-submit-order-button';
                } else {
                    this.options.is_cart = true;
                    if (!$(this.options.checkout_selector).length) {
                        console.log('Указанный селектор не удалось найти "' + this.options.checkout_selector + '"');
                        console.log('Выполняется автоматический поиск.');
                        if ($('[name=checkout]').length) {
                            console.log('Найден [name=checkout]');
                            this.options.checkout_selector = '[name=checkout]';
                        } else {
                            console.log('Селектор checkout_selector не найден');
                            return false;
                        }
                    }
                }
                return true;
            },
            orderCalculate: function () {
                var wholesale = this;
                console.log('wholesale init orderCalculate');
                $(document).ajaxComplete(function (event, xhr, settings) {
                    console.log('wholesale orderCalculate:');
                    console.log(settings);
                    console.log(settings.url === wholesale.options.order_calculate_url);
                    if (settings.url === wholesale.options.order_calculate_url) {
                        wholesale.checkCart();
                    }
                });
            },
            onestepComplete: function () {
                var wholesale = this;
                console.log('wholesale init onestepComplete');
                $(document).ajaxComplete(function (event, xhr, settings) {
                    console.log('wholesale onestepComplete:');
                    console.log(settings);
                    console.log(settings.url.indexOf(wholesale.options.onestep_url));
                    if (settings.url.indexOf(wholesale.options.onestep_url) != -1) {
                        wholesale.checkCart();
                    }
                });
            },
            cartActions: function () {
                var wholesale = this;
                console.log('wholesale init cartActions');
                $(document).ajaxComplete(function (event, xhr, settings) {
                    console.log('wholesale cartActions:');
                    console.log(settings);
                    console.log($.inArray(settings.url, wholesale.options.cart_actions));
                    if ($.inArray(settings.url, wholesale.options.cart_actions) != -1) {
                        wholesale.checkCart();
                    }
                });
            },
            disableCheckout: function (loading, message) {
                if (!$('#wholesale-cart').length) {
                    if (this.options.is_onestep) {
                        $('.onestep-cart .onestep-cart-form').after('<div id="wholesale-cart" class="hidden" style="display:none;"></div>');
                    } else {
                        $(this.options.checkout_selector).after('<div id="wholesale-cart" class="hidden" style="display:none;"></div>');
                    }
                }

                if ($('#wholesale-cart-loading').length) {
                    $('#wholesale-cart-loading').remove();
                }
                if (message === undefined) {
                    message = '';
                }
                if (loading === undefined) {
                    loading = false;
                }
                if (this.options.is_onestep) {
                    $('.onestep-cart .checkout').hide();
                    $(this.options.wholesale_selector).text(message);
                    if (message) {
                        $(this.options.wholesale_selector).removeClass('hidden').addClass('active').show();
                    } else {
                        $(this.options.wholesale_selector).removeClass('active').addClass('hidden').hide();
                    }
                    if (loading && !$('#wholesale-cart-loading').length) {
                        $('<span id="wholesale-cart-loading"><i class="icon16 loading"></i> Пожалуйста, подождите...</span>').insertBefore('.onestep-cart .checkout');
                    }
                } else {
                    $(this.options.checkout_selector).attr('disabled', true);
                    $(this.options.wholesale_selector).text(message);
                    if (message) {
                        $(this.options.wholesale_selector).removeClass('hidden').addClass('active').show();
                    } else {
                        $(this.options.wholesale_selector).removeClass('active').addClass('hidden').hide();
                    }
                    if (loading && !$('#wholesale-cart-loading').length) {
                        $('<span id="wholesale-cart-loading"><i class="icon16 loading"></i> Пожалуйста, подождите...</span>').insertBefore(this.options.checkout_selector);
                    }
                }
            },
            enableCheckout: function () {
                if ($('#wholesale-cart-loading').length) {
                    $('#wholesale-cart-loading').remove();
                }
                $(this.options.wholesale_selector).text('');
                $(this.options.wholesale_selector).removeClass('active').addClass('hidden').hide();
                if (this.options.is_onestep) {
                    $('.onestep-cart .checkout').show();
                } else {
                    $(this.options.checkout_selector).removeAttr('disabled');
                }
            },
            checkCart: function () {
                var wholesale = this;
                this.disableCheckout(true);
                $.ajax({
                    type: 'POST',
                    url: wholesale.options.url,
                    dataType: 'json',
                    success: function (data, textStatus, jqXHR) {
                        if (data.data.check.result) {
                            wholesale.enableCheckout();
                        } else {
                            wholesale.disableCheckout(false, data.data.check.message);
                        }
                    },
                    error: function (jqXHR, errorText) {
                        wholesale.enableCheckout();
                    }
                });
            }
        },
        product: {
            options: {}
            ,
            init: function (options) {
                this.options = options;
                if (!this.checkSettings()) {
                    return false;
                }
                this.options.input_quantity = $(this.options.product_cart_form_selector).find('input[name=quantity]');
                this.options.old_quantity_val = this.options.input_quantity.val();
                this.options.busy = false;
                this.options.first = true;
                this.initProductQuantityChange();
                this.initSkuChange();
                this.initUpdateProductQuantity();
            }
            ,
            checkSettings: function () {
                if (!$(this.options.product_cart_form_selector).length) {
                    console.log('Указан неверный селектор "' + this.options.product_cart_form_selector + '"');
                    return false;
                }
                if (!$(this.options.product_add2cart_selector).length) {
                    console.log('Указан неверный селектор "' + this.options.product_add2cart_selector + '"');
                    return false;
                }
                if (!$(this.options.product_cart_form_selector).find('input[name=quantity]').length) {
                    console.log('Не удалось найти "' + this.options.product_cart_form_selector + ' input[name=quantity]"');
                    return false;
                }
                return true;
            }
            ,
            initSkuChange: function () {
                $(this.options.product_cart_form_selector).find('[name=sku_id]').change(function () {
                    $(document).trigger('updateProductQuantity');
                });
                $(this.options.product_cart_form_selector).find('[name*="features"]').change(function () {
                    $(document).trigger('updateProductQuantity');
                });

            }
            ,
            initProductQuantityChange: function () {
                var $input_quantity = this.options.input_quantity;
                var quantity = '';
                setInterval(function () {
                    if (quantity != $input_quantity.val()) {
                        quantity = $input_quantity.val();
                        $(document).trigger('updateProductQuantity');
                    }
                }, 500);
            }
            ,
            checkProduct: function () {
                var wholesale = this;
                if (!wholesale.options.busy && !$(this.options.product_add2cart_selector).is(':disabled')) {
                    wholesale.options.busy = true;
                    var $form = $(this.options.product_cart_form_selector);
                    var $add2cart_button = $(this.options.product_add2cart_selector);
                    var $input_quantity = this.options.input_quantity;

                    $add2cart_button.attr('disabled', true);
                    var loading = $('<i class="icon16 loading"></i>').insertBefore($add2cart_button);
                    $.ajax({
                        type: 'POST',
                        url: wholesale.options.url,
                        data: $form.serialize() + '&old_quantity=' + wholesale.options.old_quantity_val,
                        dataType: 'json',
                        success: function (data, textStatus, jqXHR) {
                            wholesale.options.busy = false;
                            loading.remove();
                            if (data.data.check.result) {
                                $add2cart_button.removeAttr('disabled');
                            } else {
                                $input_quantity.val(data.data.check.quantity);
                                $add2cart_button.removeAttr('disabled');
                                if (wholesale.options.product_message && !wholesale.options.first) {
                                    alert(data.data.check.message);
                                }
                            }
                            wholesale.options.old_quantity_val = $input_quantity.val();
                            wholesale.options.first = false;
                        },
                        error: function (jqXHR, errorText) {
                            wholesale.options.busy = false;
                            loading.remove();
                            $add2cart_button.removeAttr('disabled');
                            //console.log(jqXHR.responseText);
                        }
                    });
                }
            }
            ,
            initUpdateProductQuantity: function () {
                var wholesale = this;
                $(document).on('updateProductQuantity', function () {
                    wholesale.checkProduct();
                });
            }
        }
        ,
        shipping: {
            disabled: false,
            message:
                '',
            inited:
                false,
            options:
                {
                    onestep_url: '',
                    url:
                        '',
                    shipping_submit_selector:
                        ''
                }
            ,
            init: function (options) {
                this.options = options;
                if (!this.checkSettings()) {
                    return false;
                }
                if (!$('#wholesale-shipping').length) {
                    if (this.options.is_onestep) {
                        $('.checkout-content[data-step-id=shipping]').after('<div id="wholesale-shipping" class="hidden" style="display:none;"></div>');
                    } else {
                        $(this.options.shipping_submit_selector).after('<div id="wholesale-shipping" class="hidden" style="display:none;"></div>');
                    }
                }
                this.options.wholesale_shipping = '#wholesale-shipping';
                this.initChangeShipping();
                if (this.options.is_onestep) {
                    this.initOnestepFormSubmit();
                    this.initAjaxComplete();
                }
                $('input[name=shipping_id]:checked').change();
                this.inited = true;
            }
            ,
            checkSettings: function () {
                /*Проверка наличия плагина заказ на одной странице*/
                if ($('.onestep-cart').length) {
                    this.options.is_onestep = true;
                    this.options.shipping_submit_selector = 'form.checkout-form #checkout-btn';
                } else {
                    this.options.is_onestep = false;
                    if (!$(this.options.shipping_submit_selector).length) {
                        console.log('Указан неверный селектор "' + this.options.shipping_submit_selector + '"');
                        return false;
                    }
                }
                return true;
            }
            ,
            initOnestepFormSubmit: function () {
                var wholesale = this;
                $('form.checkout-form').submit(function () {
                    if (!$('[name=user_type]:checked').length || $('[name=user_type]:checked').val() == 0) {
                        if (wholesale.disabled) {
                            if (wholesale.message) {
                                alert(wholesale.message)
                            }
                            return false;
                        }
                    }
                });
            }
            ,
            disableCheckout: function (loading, message) {
                console.log('disableCheckout');
                if ($('#wholesale-shipping-loading').length) {
                    $('#wholesale-shipping-loading').remove();
                }
                if (message === undefined) {
                    message = '';
                }
                if (loading === undefined) {
                    loading = false;
                }
                if (this.options.is_onestep) {
                    this.disabled = true;
                    this.message = message;
                    $(this.options.shipping_submit_selector).attr('disabled', true);
                    $(this.options.wholesale_shipping).text(message);
                    if (message) {
                        $(this.options.wholesale_shipping).removeClass('hidden').addClass('active').show();
                    } else {
                        $(this.options.wholesale_shipping).removeClass('active').addClass('hidden').hide();
                    }
                } else {
                    $(this.options.shipping_submit_selector).attr('disabled', true);
                    $(this.options.wholesale_shipping).text(message);
                    if (message) {
                        $(this.options.wholesale_shipping).removeClass('hidden').addClass('active').show();
                    } else {
                        $(this.options.wholesale_shipping).removeClass('active').addClass('hidden').hide();
                    }
                    if (loading && !$('#wholesale-shipping-loading').length) {
                        $('<span id="wholesale-shipping-loading"><i class="icon16 loading"></i> Пожалуйста, подождите...</span>').insertBefore(this.options.shipping_submit_selector);
                    }
                }
            }
            ,
            enableCheckout: function () {
                console.log('enableCheckout');
                if ($('#wholesale-shipping-loading').length) {
                    $('#wholesale-shipping-loading').remove();
                }
                if (this.options.is_onestep) {
                    this.disabled = false;
                    this.message = '';
                    $(this.options.wholesale_shipping).text('');
                    $(this.options.wholesale_shipping).removeClass('active').addClass('hidden').hide();
                    $(this.options.shipping_submit_selector).removeAttr('disabled');
                } else {
                    $(this.options.wholesale_shipping).text('');
                    $(this.options.wholesale_shipping).removeClass('active').addClass('hidden').hide();
                    $(this.options.shipping_submit_selector).removeAttr('disabled');
                }
            }
            ,
            initChangeShipping: function () {
                var wholesale = this;
                $(document).off('change', 'input[name=shipping_id]').on('change', 'input[name=shipping_id]', function () {
                    var shipping_id = $(this).val();
                    wholesale.disableCheckout(true);
                    $.ajax({
                        type: 'POST',
                        url: wholesale.options.url,
                        dataType: 'json',
                        data: {
                            shipping_id: shipping_id
                        },
                        success: function (data, textStatus, jqXHR) {
                            if (data.data.check.result) {
                                wholesale.enableCheckout();
                            } else {
                                wholesale.disableCheckout(false, data.data.check.message);
                            }
                        },
                        error: function (jqXHR, errorText) {
                        }
                    });
                });
                $('input[name=shipping_id]:checked').change();
            }
            ,
            initAjaxComplete: function () {
                var wholesale = this;
                $(document).ajaxComplete(function (event, xhr, settings) {
                    if (settings.url == wholesale.options.onestep_url) {
                        setTimeout(function () {
                            if (wholesale.disabled) {
                                wholesale.disableCheckout(false, wholesale.message);
                            } else {
                                wholesale.enableCheckout();
                            }
                        }, 300);
                    }
                });
            }
        }
    }
    ;
})(jQuery);