function Product(form, options) {
    this.form = $(form);
    this.add2cart = this.form.find(".add2cart");
    this.button = this.add2cart.find("input[type=submit]");
    for (var k in options) {
        this[k] = options[k];
    }
    var self = this;
    // add to cart block: services
    this.form.find(".services input[type=checkbox]").click(function () {
        var obj = $('select[name="service_variant[' + $(this).val() + ']"]');
        if (obj.length) {
            if ($(this).is(':checked')) {
                obj.removeAttr('disabled');
            } else {
                obj.attr('disabled', 'disabled');
            }
        }
        self.cartButtonVisibility(true);
        self.updatePrice();
    });

    this.form.find(".services .service-variants").on('change', function () {
        self.cartButtonVisibility(true);
        self.updatePrice();
    });

    this.form.find(".skus input[type=radio]").click(function () {
        if ($(this).data('image-id')) {
            $("#product-image-" + $(this).data('image-id')).click();
        }
        if ($(this).data('disabled')) {
            self.button.attr('disabled', 'disabled');
            self.form.find('.purchase').addClass('purchase-disabled');
        } else {
            self.button.removeAttr('disabled');
            self.form.find('.purchase').removeClass('purchase-disabled');
        }
        var sku_id = $(this).val();
        self.updateSkuServices(sku_id);
        self.cartButtonVisibility(true);
        self.updatePrice();
    });
    var $initial_cb = this.form.find(".skus input[type=radio]:checked:not(:disabled)");
    if (!$initial_cb.length) {
        $initial_cb = this.form.find(".skus input[type=radio]:not(:disabled):first").prop('checked', true).click();
    }
    $initial_cb.click();

    this.form.find(".sku-feature").change(function () {
        var key = "";
        self.form.find(".sku-feature:not(input), .sku-feature:checked").each(function () {
            key += $(this).data('feature-id') + ':' + $(this).val() + ';';
        });
        var sku = self.features[key];
        if (sku) {
            if (sku.image_id) {
                $("#product-image-" + sku.image_id).click();
            }
            self.updateSkuServices(sku.id);
            if (sku.available) {
                self.button.removeAttr('disabled');
                self.form.find('.purchase').removeClass('purchase-disabled');
                //fix plugin.quickorder
                $(".quickorder-custom-button, .quickorder-custom-form.cancel-popup").addClass('quickorder-custom-show');
            } else {
                self.form.find("div.stocks div").hide();
                self.form.find(".sku-no-stock").show();
                self.button.attr('disabled', 'disabled');
                self.form.find('.purchase').addClass('purchase-disabled');
                //fix plugin.quickorder
                $(".quickorder-custom-button, .quickorder-custom-form.cancel-popup").removeClass('quickorder-custom-show');
            }
            self.add2cart.find(".price").data('price', sku.price);
            self.updatePrice(sku.price, sku.compare_price);
        } else {
            self.form.find("div.stocks div").hide();
            self.form.find("div.selectable-articul div").hide();
            self.form.find(".sku-no-stock").show();
            self.button.attr('disabled', 'disabled');
            self.form.find('.purchase').addClass('purchase-disabled');
            //fix plugin.quickorder
            $(".quickorder-custom-button, .quickorder-custom-form.cancel-popup").removeClass('quickorder-custom-show');
            self.add2cart.find(".compare-at-price").hide();
            self.add2cart.find(".price").empty();
        }
        self.cartButtonVisibility(true);
    });
    this.form.find(".sku-feature:first").change();

    if (!this.form.find(".skus input:radio:checked").length) {
        this.form.find(".skus input:radio:enabled:first").attr('checked', 'checked');
    }
    
    this.form.find(".qty-wrapper input").change(function(){
        var max = $(this).data('max');
      	if(parseInt($(this).val()) > 0){
          	if(max && parseInt($(this).val()) > parseInt(max)){
            	$(this).val(parseInt(max));
          		alert($.at.t('Much product is no longer available!'));
            }
			$(this).val(parseInt($(this).val()));
        }else{
        	$(this).val(1);
        }
    });
    
    this.form.find(".qty-plus").click(function(){
        var inp = $(this).parent().find('input');
        inp.val(parseInt(inp.val()) + 1);
        inp.change();
        return false;
    });
    
    this.form.find(".qty-minus").click(function(){
        var inp = $(this).parent().find('input');
        inp.val(parseInt(inp.val()) - 1);
        inp.change();
        return false;
    });

    this.form.submit(function () {
        var f = $(this);
        f.find('.adding2cart').addClass('icon24 loading').show();

        $.post(f.attr('action') + '?html=1', f.serialize(), function (response) {
            f.find('.adding2cart').hide();
            if (response.status == 'ok') {
                var cart_total = $(".cart-total");
                var cart_count = $(".cart-count");
                var cart_div = f.closest('.cart');

                cart_total.closest('#cart').removeClass('empty');

                self.cartButtonVisibility(false);
                if ( !( MatchMedia("only screen and (max-width: 992px)") ) ) {

                    // flying cart
                    var clone = $('<div class="cart"></div>').append(f.clone());
                    clone.appendTo('body');
                    clone.css({
                        'z-index': 100500,
                        background: cart_div.closest('.dialog').length ? '#fff' : cart_div.parent().css('background'),
                        top: cart_div.offset().top,
                        left: cart_div.offset().left,
                        width: cart_div.width() + 'px',
                        height: cart_div.height() + 'px',
                        position: 'absolute',
                        overflow: 'hidden'
                    }).css({'border':'2px solid #eee','padding':'20px','background':'#fff'}).animate({
                        top: $('#cart').offset().top,
                        left: $('#cart').offset().left,
                        width: '10px',
                        height: '10px',
                        opacity: 0.7
                    }, 600, function () {
                        $(this).remove();
                        cart_total.html(response.data.total);
                        cart_count.attr('data-count', response.data.count).hide().show();
                    });
                    
                    var $fcd = self.form.find('.flying-cart-data').data();
                    if($fcd && $('#flying-cart').length){
                        var $item = $('#flying-cart li[data-id="'+response.data.item_id+'"]');
                        var cnt = parseInt(self.form.find('input[name="quantity"]').val()) || 1;
                        
                        if($item.length){
                            var $qty = $item.find('input.flying-cart-qty');
                            cnt += parseInt($qty.val());
                            $qty.val(cnt);
                            //$item.find('.price').html($fcd.price);
                        }else{
                            $fcd = $.extend({}, $fcd, { id: response.data.item_id, cnt: cnt, price: $fcd.price });
                            $('#flying-cart ul').prepend(newItem($fcd));
                            $.at.shop.setFlyingHeight();
                        }
                        
                        $('#flying-cart').scrollTop( $('#flying-cart li[data-id="'+response.data.item_id+'"]').position().top );
                    }
                    
                    if (cart_div.closest('.dialog').length) {
                        cart_div.closest('.dialog').hide().find('.cart').empty();
                        $('body, #footer-pane').removeClass('dialog-margin');
                    }

                } else {

                    // mobile: added to cart message
                    cart_total.html(response.data.total);
                    cart_count.attr('data-count', response.data.count).hide().show();
                }

                if (f.data('cart') || $('.cart-summary-page').length) {
                    $("#page-content").load(location.href, function () {
                        $("#dialog").hide().find('.cart').empty();
                        $('body, #footer-pane').removeClass('dialog-margin');
                    });
                }
                if (response.data.error) {
                    alert(response.data.error);
                }
            } else if (response.status == 'fail') {
                alert(response.errors);
            }
        }, "json");

        return false;
    });

}

Product.prototype.currencyFormat = function (number, no_html) {
    // Format a number with grouped thousands
    //
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +	 bugfix by: Michael White (http://crestidg.com)

    var i, j, kw, kd, km;
    var decimals = this.currency.frac_digits;
    var dec_point = this.currency.decimal_point;
    var thousands_sep = this.currency.thousands_sep;

    // input sanitation & defaults
    if( isNaN(decimals = Math.abs(decimals)) ){
        decimals = 2;
    }
    if( dec_point == undefined ){
        dec_point = ",";
    }
    if( thousands_sep == undefined ){
        thousands_sep = ".";
    }

    i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

    if( (j = i.length) > 3 ){
        j = j % 3;
    } else{
        j = 0;
    }

    km = (j ? i.substr(0, j) + thousands_sep : "");
    kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
    //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
    kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


    var number = km + kw + kd;
    var s = no_html ? this.currency.sign : this.currency.sign_html;
    if (!this.currency.sign_position) {
        return s + this.currency.sign_delim + number;
    } else {
        return number + this.currency.sign_delim + s;
    }
};


Product.prototype.serviceVariantHtml= function (id, name, price) {
    return $('<option data-price="' + price + '" value="' + id + '"></option>').text(name + ' (+' + this.currencyFormat(price, 1) + ')');
};

Product.prototype.updateSkuServices = function (sku_id) {
    this.form.find("div.stocks div").hide();
    this.form.find(".sku-" + sku_id + "-stock").show();
    this.form.find("div.selectable-articul div").hide();
    this.form.find(".sku-" + sku_id + "-articul").show();
    this.form.find('input[name="quantity"]').data('max', this.form.find(".sku-" + sku_id + "-stock").data('sku-count'));
    for (var service_id in this.services[sku_id]) {
        var v = this.services[sku_id][service_id];
        if (v === false) {
            this.form.find(".service-" + service_id).hide().find('input,select').attr('disabled', 'disabled').removeAttr('checked');
        } else {
            this.form.find(".service-" + service_id).show().find('input').removeAttr('disabled');
            if (typeof (v) == 'string') {
                this.form.find(".service-" + service_id + ' .service-price').html(this.currencyFormat(v));
                this.form.find(".service-" + service_id + ' input').data('price', v);
            } else {
                var select = this.form.find(".service-" + service_id + ' .service-variants');
                var selected_variant_id = select.val();
                for (var variant_id in v) {
                    var obj = select.find('option[value=' + variant_id + ']');
                    if (v[variant_id] === false) {
                        obj.hide();
                        if (obj.attr('value') == selected_variant_id) {
                            selected_variant_id = false;
                        }
                    } else {
                        if (!selected_variant_id) {
                            selected_variant_id = variant_id;
                        }
                        obj.replaceWith(this.serviceVariantHtml(variant_id, v[variant_id][0], v[variant_id][1]));
                    }
                }
                this.form.find(".service-" + service_id + ' .service-variants').val(selected_variant_id);
            }
        }
    }
};
Product.prototype.updatePrice = function (price, compare_price) {
    if (price === undefined) {
        var input_checked = this.form.find(".skus input:radio:checked");
        if (input_checked.length) {
            var price = parseFloat(input_checked.data('price'));
            var compare_price = parseFloat(input_checked.data('compare-price'));
        } else {
            var price = parseFloat(this.add2cart.find(".price").data('price'));
        }
    }
    if (compare_price) {
        if (!this.add2cart.find(".compare-at-price").length) {
            this.add2cart.prepend('<span class="compare-at-price nowrap"></span>');
        }
        this.add2cart.find(".compare-at-price").html(this.currencyFormat(compare_price)).show();
    } else {
        this.add2cart.find(".compare-at-price").hide();
    }
    var self = this;
    this.form.find(".services input:checked").each(function () {
        var s = $(this).val();
        if (self.form.find('.service-' + s + '  .service-variants').length) {
            price += parseFloat(self.form.find('.service-' + s + '  .service-variants :selected').data('price'));
        } else {
            price += parseFloat($(this).data('price'));
        }
    });
    this.add2cart.find(".price").html(this.currencyFormat(price));
    this.form.find('input[name="quantity"]').val(1);
}

Product.prototype.cartButtonVisibility = function (visible) {
    //toggles "Add to cart" / "%s is now in your shopping cart" visibility status
    if (visible) {
        this.add2cart.find('.submit-wrapper').show();
        this.add2cart.find('.price-wrapper').show();
        this.add2cart.find('.qty-wrapper').show();
        this.add2cart.find('span.added2cart').hide();
    } else {
        if ( MatchMedia("only screen and (max-width: 992px)") ) {
            this.add2cart.find('.submit-wrapper').hide();
            this.add2cart.find('.price-wrapper').hide();
            this.add2cart.find('.qty-wrapper').hide();
            this.add2cart.find('span.added2cart').show();
        }
    }
}

$(function () {
    if ($(".product-gallery > .image a").length) {
        $(".product-gallery > .image").off('click.coreimg').on('click.coreimg', 'a', function (e) {
            e.preventDefault();
            var images = [];
            var that = $(this).closest('.product-gallery');
            if (that.find("#product-gallery a").length) {
                var k = 0;
                that.find('#product-gallery div.image').each(function (i) {
                    images.push({href: $(this).find('a').attr('href'), title: $(this).find('img').attr('title')});
                    if ($(this).hasClass('selected')) {
                        k = i;
                    }
                });
                if (k) {
                    images = images.slice(k).concat(images.slice(0, k));
                }
            } else {
                images.push({href: $(this).attr('href'), title: $(this).find('img').attr('title')});
            }
            
            $.fancybox(images, {
                closeBtn: false,
                helpers: {
        			title: { type : 'inside' },
        			buttons	: { skipSingle: true }
        		}
            });
            
            return false;
        });
    }
    
    $(".more-images-slider").owlCarousel({
        loop: false,
        dots: false,
        items: 2,
        nav: true,
        navText: ["",""],
        responsive: {
            0: {
                items: 2
            },
            481: {
                items: 3
            },
            741: {
                items: 4
            },
            993: {
                items: $('.without-sidebar').length == 0 ? 5 : 4
            },
            1281: {
                items: $('.without-sidebar').length == 0 ? 6 : 5
            }
        }
    });
    
    // product image video
    $('#product-image-video').click(function () {
        $('#product-core-image').hide();
        $('#video-container').show();
        $('.more-images .image').removeClass('selected');
        $(this).parent().addClass('selected');
        return false;
    });
    
    // product images
    $(".more-images a").not('#product-image-video').click(function () {
        var that = $(this).closest('.product-gallery');
        that.find('.image').removeClass('selected');
        $(this).parent().addClass('selected');
        
        $('#product-core-image').show();
        $('#video-container').hide();

        that.find("#product-image").addClass('blurred');
        that.find("#switching-image").show();

        var img = $(this).find('img');
        var size = that.find("#product-image").attr('src').replace(/^.*\/[^\/]+\.(.*)\.[^\.]*$/, '$1');
        var src = img.attr('src').replace(/^(.*\/[^\/]+\.)(.*)(\.[^\.]*)$/, '$1' + size + '$3');
        $('<img>').attr('src', src).load(function () {
            that.find("#product-image").attr('src', src);
            that.find("#product-image").removeClass('blurred');
            that.find("#switching-image").hide();
        }).each(function() {
            //ensure image load is fired. Fixes opera loading bug
            if (this.complete) { $(this).trigger("load"); }
        });
        var size = that.find("#product-image").parent().attr('href').replace(/^.*\/[^\/]+\.(.*)\.[^\.]*$/, '$1');
        var href = img.attr('src').replace(/^(.*\/[^\/]+\.)(.*)(\.[^\.]*)$/, '$1' + size + '$3');
        that.find("#product-image").parent().attr('href', href);
        return false;
    });

    $('#product-tabs-nav a:visible').click(function(){
        var self = $(this);
        var tab_id = self.data('name');
        if (!tab_id) {
            return true;
        }
        
        var tab = $('#product-tab-' + tab_id);
        
        $('#product-tabs-nav .selected').removeClass('selected');
        
        if (!tab.length) {
            tab = $('<div id="product-tab-' + tab_id + '" class="product-tab"></div>');
            var current_tab = $('.product-tab:visible');
            current_tab.css('opacity', 0);
            $('#product-tabs').find('.product-tabs-nav-trigger[data-name="' + tab_id + '"]').parent().after(tab);
            $.at.o.a($('#product-tabs'), 32);
            var url = self.attr('href');
            if (url) {
                $.get(url, { }, function(response){
                    var _tmp = $(response).find('.product-info');
                    $.at.o.r($('#product-tabs'));
                    tab.html(_tmp);
                    current_tab.css('opacity', 1).hide();
                    $(window).trigger('scroll');
                });
            }
        } else {
            $('.product-tab').hide();
        }
        
        tab.show();
        self.parent().addClass('selected');
        
        // Save events for plugin flying cart
        $(window).trigger('scroll');
        
        return false;
    });
    var tab = $('#product-tabs-nav .selected a');
    if (!tab.length) {
        tab = $('#product-tabs-nav a:first');
    }
    tab.click();
    
    
    
    window.tabTimer = null;
    $(window).on('scroll.tab', function() {
    	if ( window.tabTimer ) {
            clearTimeout(window.tabTimer);
        }
        //if (MatchMedia("only screen and (min-width: 993px)")) {
        
            window.tabTimer = setTimeout(function() {
            
                var min_top = $(document).scrollTop(),
                    max_top = min_top + $(window).height();
                
            	var tab_triggers = $('.product-tabs-nav-trigger:visible');
            	tab_triggers.each(function () {
            	    if ($(this).offset().top + $(this).outerHeight() < max_top) {
            	        var tab_id = $(this).data('name');
            	        if (!tab_id) {
                            return true;
                        }
                        var tab = $('#product-tab-' + tab_id);
                        if (!tab.length) {
                            tab = $('<div id="product-tab-' + tab_id + '" class="product-tab"><i class="icon32 loading"></i></div>');
                            
                            $(this).parent().after(tab);
                            
                            var url = $(this).attr('href');
                            if (url) {
                                $.get(url, { }, function(response){
                                    var _tmp = $(response).find('.product-info');
                                    tab.html(_tmp);
                                    $(window).trigger('scroll');
                                });
                            }
                        }
            	    }
            	});
            	
            }, 100);
        
        //}
    });
    
    $(window).resize(function(){
        if ( MatchMedia("only screen and (max-width: 992px)") ) {
            $('.product-tab').attr('style', '');
        } else {
            var tab = $('#product-tabs-nav .selected a');
            if (!tab.length) {
                tab = $('#product-tabs-nav a:first');
            }
            tab.click();
        }
    });
    
    //print product page
  	$('#product-print').click(function() {  
		window.print();  
		return false;  
	});
    
});

;(function($, window, document, undefined) {
    
    function stickyCart(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, stickyCart.defaults, options);
        
        this.setup();
    };
    
    stickyCart.defaults = {
        parent: null, //selector # || .
        margin_top: 0,
        refresh_rate: 300,
        animate_speed: 500 //ms
    };
    
    stickyCart.prototype.setup = function() {
        if (!this.options.parent) {
            return false;
        }
        
        this.$parent = $(this.options.parent);
        this.$parent.css('position', 'relative');
        this._initial_top = this.$element.position().top;
        
        this.$element.css({ transition: 'top ' + (this.options.animate_speed/1000) + 's ease' });
        
        this.addEvents();
    };
    
    stickyCart.prototype.addEvents = function() {
        this.resizeTimer = null;
        $(window).on('resize', $.proxy(function(){
            window.clearTimeout(this.resizeTimer);
    		this.resizeTimer = window.setTimeout($.proxy(function(e){
    		    this.onScroll(e);
    		}, this), 50);
        }, this));
        $(window).on('scroll', $.proxy(function(e) {
            window.clearTimeout(this.resizeTimer);
    		this.resizeTimer = window.setTimeout($.proxy(function(e){
    		    if (MatchMedia("only screen and (min-width: 993px)")) {
                    this.onScroll(e);
                }
    		}, this), this.options.refresh_rate);
        }, this));
        this.onScroll();
    };
    
    stickyCart.prototype.onScroll = function() {
        this._parent_height = this.$parent.innerHeight();
        this._element_height = this.$element.innerHeight();
        
        if ( MatchMedia("only screen and (max-width: 992px)") ||
             $(window).height() < (this.options.margin_top + (this._element_height * 1.1)) ||
             (this._parent_height < this._element_height * 1.5) ) {
            
            this.$element.css({ top: this._initial_top });
            setTimeout($.proxy(function(){
                this.$element.css({ position: 'static' }).removeClass('fly');
            }, this), this.options.animate_speed);
            
            return false;
        }
        
        this._parent_top = this.$parent.offset().top + this._initial_top;
        this._parent_bottom = this._parent_top + this._parent_height - this._element_height - this._initial_top - this.options.margin_top;
        this._scroll_top = $(window).scrollTop();
        
      	if ( this._scroll_top >= this._parent_top && this._scroll_top < this._parent_bottom ) {
            
            this.$element.css({ top: this._scroll_top - this._parent_top + this._initial_top + this.options.margin_top, position: 'absolute' }).addClass('fly');
            
        } else if ( this._scroll_top >= this._parent_top && this._scroll_top >= this._parent_bottom ) {
            
            this.$element.css({ top: this._parent_height - this._element_height, position: 'absolute' }).addClass('fly');
            
    	} else if ( this._scroll_top < this._parent_top ) {
    	
    	    this.$element.css({ top: this._initial_top });
    	    
    	    setTimeout($.proxy(function(){
                this.$element.css({ position: 'static' }).removeClass('fly');
            }, this), this.options.animate_speed);
    	    
    	}
    };
    
    $.fn.stickyCart = function(options) {
    	return this.each(function() {
    		if (!$(this).data('stickyCart')) {
    			$(this).data('stickyCart', new stickyCart(this, options));
    		}
    	});
    };
    $.fn.stickyCart.Constructor = stickyCart;
    
})(window.jQuery, window, document);

$(window).load(function(){
    // scroll-dependent animations
    if (!$('.without-sidebar').length) {
        $('#cart-flyer').stickyCart({ parent: '.product-wrapper', margin_top: 15, refresh_rate: 500 });
    }
});