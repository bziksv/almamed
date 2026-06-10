$.at = $.at || { };
$.at.addition = {
    init: function(){
        $("#addition-all-delete").click(function () {
            $.cookie('shop_' + $(this).data('addition'), null, { expires: 0, path: '/'});
            
            var _tmp = $('#page-content');
            $('.content').html('<div class="fill-background"><img src="' + $(this).data('loading') + '" style="float: right;"><h1>' + _tmp.find('h1').html() + '</h1></div>');
            window.location = location.href.replace(/compare\/.*$/, 'compare/');
            
            return false;
        });
        /*
        $('#bookmark-link, #compare-link, #viewed-link').click(function(){
            if ($(this).hasClass('gray')) {
                return false;
            }
        });
        */
        //compare
        $('#page-content').off('click.compare').on('click.compare', '.compare-add', function (event) {
            var compare = $.cookie('shop_compare');
            
            if($(this).hasClass('added')){
                if (compare) { compare = compare.split(','); } else { compare = []; }
                var i = $.inArray($(this).data('product') + '', compare);
                if (i != -1) { compare.splice(i, 1); }
                if (compare.length) {
                    $.cookie('shop_compare', compare.join(','), { expires: 30, path: '/'});
                    var url = $("#compare-link").data('href').replace(/compare\/.*$/, 'compare/' + compare.join(',') + '/');
                } else {
                    $.cookie('shop_compare', null, { expires: 0, path: '/'});
                    var url = $("#compare-link").data('href').replace(/compare\/.*$/, 'compare/');
                }
                
                $("#compare-link").attr('href', url);
                $('.compare-add.added[data-product="' + $(this).data('product') + '"]').removeClass('added');
                $("#compare-link span.count").text(compare.length);
                if (compare.length < 2) { $("#compare-link").addClass('gray').removeClass('added'); }
            }else{
                if (compare) { compare = $(this).data('product') + ',' + compare; } else { compare = '' + $(this).data('product'); }
                
                var url = $("#compare-link").data('href').replace(/compare\/.*$/, 'compare/' + compare + '/');
                $("#compare-link").attr('href', url);
                $("#compare-link span.count").text(compare.split(',').length);
                if (compare.split(',').length > 1) { $("#compare-link").removeClass('gray').addClass('added'); }
            	
                $.cookie('shop_compare', compare, { expires: 30, path: '/'});
                $('.compare-add[data-product="' + $(this).data('product') + '"]').addClass('added');
                $.at.addition.blinkAddition($("#compare-link"));
            }
            var title = $(this).data('title');
            $(this).data('title', $(this).attr('title'));
            $(this).attr('title', title);
            
            return false;
        });
        //bookmark
        $('#page-content').off('click.bookmark').on('click.bookmark', '.bookmark-add', function (event) {
            var bookmark = $.cookie('shop_bookmark');
            
            if($(this).hasClass('added')){
                if (bookmark) { bookmark = bookmark.split(','); } else { bookmark = []; }
                var i = $.inArray($(this).data('product') + '', bookmark);
                if (i != -1) { bookmark.splice(i, 1); }
                if (bookmark.length) { 
                    $.cookie('shop_bookmark', bookmark.join(','), { expires: 30, path: '/'});
                } else {
                    $.cookie('shop_bookmark', null, { expires: 0, path: '/'});
                }
                
                $('.bookmark-add.added[data-product="' + $(this).data('product') + '"]').removeClass('added');
                
                if(!bookmark.length){
                    if($('#product-list.bookmark').length){
                        var _tmp = $('#page-content');
                        $('#page-content').html('<img src="' + $('#addition-all-delete').data('loading') + '" style="float: right;"><h1>' + _tmp.find('h1').html() + '</h1>');
                        window.location = location.href;
                        return false;
                    }else{
                        $("#bookmark-link").addClass('gray').removeClass('added');
                    }
                }
                if($('#product-list.bookmark').length) $(this).closest('li').remove();
                $("#bookmark-link span.count").text(bookmark.length);
            }else{
                if (bookmark) { bookmark = $(this).data('product') + ',' + bookmark; } else { bookmark = '' + $(this).data('product'); }
                
                $("#bookmark-link span.count").text(bookmark.split(',').length);
                if (bookmark.split(',').length > 0) { $("#bookmark-link").removeClass('gray').addClass('added'); }
            	
                $.cookie('shop_bookmark', bookmark, { expires: 30, path: '/'});
                $('.bookmark-add[data-product="' + $(this).data('product') + '"]').addClass('added');
                $.at.addition.blinkAddition($("#bookmark-link"));
            }
            var title = $(this).data('title');
            $(this).data('title', $(this).attr('title'));
            $(this).attr('title', title);
            
            return false;
        });
    },
    addToViewed: function(id, vcount, vreverse){
        if (vcount === undefined || vcount <= 0 ) { vcount = 30; }
        if (vreverse === undefined) { vreverse = true; }
        var viewed = $.cookie('shop_viewed');
        if (viewed) { viewed = viewed.split(','); } else { viewed = []; }
        var i = $.inArray(id + '', viewed);
        if (i != -1) { viewed.splice(i, 1); }
        if (vreverse) { viewed.reverse(); }
        if (viewed.length >= vcount) { viewed.shift(); viewed[viewed.length] = id + ''; } else { viewed[viewed.length] = id + ''; }
        if (vreverse) { viewed.reverse(); }
        $.cookie('shop_viewed', viewed.join(','), { expires: 30, path: '/'});
    },
    blinkAddition: function(e){
        clearTimeout(e.data('blink'));
        e.removeClass('blink').addClass('blink');
        e.data('blink', setTimeout(function(){
            e.removeClass('blink');
        }, 1000));
    }
};
//iLAZY.LOAD LIST
;(function($, window, document, undefined) {

    function PLlazyload(element, options) {
        this.settings = null;
        this.$element = $(element);
        this.options = $.extend({}, PLlazyload.Defaults, options);
        this._breakpoint = null;
        
        this.setup();
    	this.initialize();
    };
    PLlazyload.Defaults = {
    	items: 5,
    	label: 'Show more',
    	responsive: {}
    };
    PLlazyload.prototype.setup = function() {
    	this._width = this.$element.width();
    	
    	var viewport = this.viewport(),
    		overwrites = this.options.responsive,
    		match = -1,
    		settings = null;
    
    	if (!overwrites) {
    		settings = $.extend({}, this.options);
    	} else {
    		$.each(overwrites, function(breakpoint) {
    			if (breakpoint <= viewport && breakpoint > match) {
    				match = Number(breakpoint);
    			}
    		});
    
    		settings = $.extend({}, this.options, overwrites[match]);
    		delete settings.responsive;
    	}
    
    	if (this.settings === null || this._breakpoint !== match) {
    		this._breakpoint = match;
    		this.settings = settings;
    	}
    };
    PLlazyload.prototype.initialize = function() {
        if (!!this.options.responsive) {
            this.addResize();
    	}
    	
    	this.$element.find('li').hide();
    	this.button = $('<div style="clear: both; text-align: center;"><a href="#" class="lazyloading-load-more button">' + this.settings.label + '</a></div>');
    	this.button.click($.proxy(function(){
    	    this.$element.find('li:hidden:lt(' + this.settings.items + ')').each(function(i, e){
                var $e = $(e);
                $e.show();
                setTimeout(function(){
                    $e.addClass('ready');
                }, i * 80);
            });
            if (!this.$element.find('li:hidden').length) {
                this.button.hide();
            }
            return false;
    	}, this));
    	this.$element.append(this.button);
    	this.button.click();
    };
    PLlazyload.prototype.viewport = function() {
    	var width;
    	if (window.innerWidth) {
    		width = window.innerWidth;
    	} else if (document.documentElement && document.documentElement.clientWidth) {
    		width = document.documentElement.clientWidth;
    	} else {
    		throw 'Can not detect viewport width.';
    	}
    	return width;
    };
    PLlazyload.prototype.addResize = function() {
        $(window).on('resize', $.proxy(function(){
            clearTimeout(this.resizeTimer);
        	this.resizeTimer = setTimeout($.proxy(function(e) { this.onResize(e); }, this), 200);
    	}, this));
    };
    PLlazyload.prototype.onRefresh = function() {
        this.$element.find('li').hide().removeClass('ready');
        this.button.show();
    };
    PLlazyload.prototype.onResize = function() {
        if (this._width != this.$element.width()) {
            this.onRefresh();
            this.setup();
            this.button.click();
        }
    };
    $.fn.plLazyload = function(options) {
    	return this.each(function() {
    		if (!$(this).data('plLazyload')) {
    			$(this).data('plLazyload', new PLlazyload(this, options));
    		}
    	});
    };
    $.fn.plLazyload.Constructor = PLlazyload;
})(window.jQuery, window, document);

( function ($, undefined) {
    
    var bindEvents = function () {
        
        $('.js-promo-countdown').each(function () {
            var $this = $(this).html('');
            var id = ($this.attr('id') || 'js-promo-countdown' + ('' + Math.random()).slice(2));
            $this.attr('id', id);
            var start = $this.data('start').replace(/-/g, '/');
            var end = $this.data('end').replace(/-/g, '/');
            $this.countdowntimer({
                startDate: start,
                dateAndTime: end,
                size: 'lg'
            });
        });
        
    };
    
    $(document).ready(function () {
        
        if ( $('.js-promo-countdown').length ) {
            $.at.pL({ label: 'jquery.countdowntimer', success: bindEvents });
        }
        
    });
    
})(jQuery);

$(document).ready(function () {
    //CURRENCY
    $('body').off('change.currency').on('change.currency', '#currency', function() {
        var url = location.href;
        if (url.indexOf('?') == -1) {
            url += '?';
        } else {
            url += '&';
        }
        location.href = url + 'currency=' + $(this).val();
    });
    
    //BULLETS
    $('.bullet .bullet-button').click(function(){
        var bullet = $(this).closest('.bullet'), body = $(this).closest('.bullet').find('.bullet-body');
        bullet.removeClass('active');
        if (body.is(':hidden')) {
            body.slideDown(300);
            bullet.addClass('active');
        } else {
            body.slideUp(300);
        }
        return false;
    });
    
    //SORTING
    $('#product-list').off('change.sorting').on('change.sorting', '#sorting', function(){
        location.assign($(this).val());
    });
    //SHOW BY
    $('#product-list').off('change.showby').on('change.showby', '#products-per-page', function(){
        var pppc = $(':selected', this).data('pppc');
        if(pppc){
            $.cookie('products_per_page', pppc, { expires: 30, path: '/'});
        }else{
            $.cookie('products_per_page', '', { expires: 0, path: '/'});
        }
        location.assign($(this).val());
    });
    //VIEW SELECT
    $('#product-list').off('click.selectview').on('click.selectview', '#select-view li', function(){
		$('#select-view li').removeClass('selected');
		switch ($(this).attr('class')) {
		    case 'thumbs':
		        $('#product-list .product-list').addClass('thumbs').removeClass('list short-list');
			    $.cookie('shop_select_view', 'thumbs', {expires: 30, path: '/'});
		    break;
		    case 'list':
		        $('#product-list .product-list').addClass('list').removeClass('thumbs short-list');
			    $.cookie('shop_select_view', 'list', {expires: 30, path: '/'});
		    break;
		    case 'short-list':
		        $('#product-list .product-list').addClass('short-list').removeClass('thumbs list');
			    $.cookie('shop_select_view', 'short-list', {expires: 30, path: '/'});
		    break;
		}
		$(this).addClass('selected');
		return false;
	});
    

    //SLIDERS
    //$('.homepage-bxslider').bxSlider( { auto : $('.homepage-bxslider li').length > 1, pause : 5000, autoHover : true, pager: $('.homepage-bxslider li').length > 1 });
    //$('.homepage-bxslider').css('height','auto');
    
    $(".product-list.thumbs.carousel").owlCarousel({
        //loop: true,
        dots: false,
        items: 1,
        nav: true,
        navText: ["",""],
        responsive: {
            0: {
                items: 1
            },
            481: {
                items: 2
            },
            741: {
                items: 3
            },
            993: {
                items: $('.without-sidebar').length == 0 ? 4 : 3
            },
            1281: {
                items: $('.without-sidebar').length == 0 ? 5 : 4
            }
        }
    });
    
    $(".product-list.lazyload:not(.carousel)").plLazyload({
        label: $.at.t('Show more'),
        responsive: {
            0: {
                items: 1
            },
            481: {
                items: 2
            },
            741: {
                items: 3
            },
            993: {
                items: $('.without-sidebar').length == 0 ? 4 : 3
            },
            1281: {
                items: $('.without-sidebar').length == 0 ? 5 : 4
            }
        }
        
    });
    
    //OPEN PREVIEW
    if ($.at.shop.product_preview) {
    
        $(".container").on('click.preview', '.product-list .image-setting-product-preview', function () {
            var b = $(this);
            
            var d = $('#dialog');
            var c = d.find('.cart');
            c.append('<i class="icon32 loading"></i>');
            d.show();
            $('body, #footer-pane').addClass('dialog-margin');
            
            c.load(b.data('url'), function () {
                c.prepend('<a href="#" class="dialog-close"><i class="material-icons mi-2x">&#xE5CD;</i></a>');
            });
            
            return false;
        });
    
    }
    
    //PREV/NEXT IMAGE
    if ($.at.shop.scroll_image) {
    
        $(".container").on('click.prev_image', '.product-list .image-setting-prev', function () {
            var img = $(this).closest('.image').find('img'),
            images = $(this).parent().data('images'),
            src = scrollImage(img.attr('src'), images, 'prev');
            
            $('<img>').attr('src', src).load(function () {
                img.attr('src', src);
            }).each(function() {
                //ensure image load is fired. Fixes opera loading bug
                if (this.complete) { $(this).trigger("load"); }
            });
            
            return false;
        });
        $(".container").on('click.prev_image', '.product-list .image-setting-next', function () {
            var img = $(this).closest('.image').find('img'),
            images = $(this).parent().data('images'),
            src = scrollImage(img.attr('src'), images, 'next');
            
            $('<img>').attr('src', src).load(function () {
                img.attr('src', src);
            }).each(function() {
                //ensure image load is fired. Fixes opera loading bug
                if (this.complete) { $(this).trigger("load"); }
            });
            
            return false;
        });
        
    }

    //ADD TO CART
    $(".container").on('submit', '.product-list form.addtocart', function () {
        var f = $(this);
        f.find('.adding2cart').addClass('icon16 loading').show();
        if (f.data('url')) {
            var d = $('#dialog');
            var c = d.find('.cart');
            c.append('<i class="icon32 loading"></i>');
            d.show();
            $('body, #footer-pane').addClass('dialog-margin');
            
            c.load(f.data('url'), function () {
                f.find('.adding2cart').hide();
                c.prepend('<a href="#" class="dialog-close"><i class="material-icons mi-2x">&#xE5CD;</i></a>');
            });
            return false;
        }
        $.post(f.attr('action') + '?html=1', f.serialize(), function (response) {
            f.find('.adding2cart').hide();
            
            if (response.status == 'ok') {
                
                var cart_total = $(".cart-total");
                var cart_count = $(".cart-count");
                cart_total.closest('#cart').removeClass('empty');

                if ( MatchMedia("only screen and (max-width: 992px)") ) {
                
                    // mobile: show "added to cart" message
                    f.find('.submit-wrapper').hide();
                    f.find('.price-wrapper').hide();
                    f.find('span.added2cart').show();
                    cart_total.html(response.data.total);
                    cart_count.attr('data-count', response.data.count).hide().show();
                    
                } else {
                
                    // flying cart
                    var origin = f.closest('li');
                    var block = $('<div></div>').append(origin.html());
                    block.css({
                        'z-index': 100500,
                        background: '#fff',
                        top: origin.offset().top,
                        left: origin.offset().left,
                        width: origin.width()+'px',
                        height: origin.height()+'px',
                        position: 'absolute',
                        overflow: 'hidden'
                    }).appendTo('body').css({'border':'2px solid #eee','padding':'20px','background':'#fff'}).animate({
                        top: $('#cart').offset().top,
                        left: $('#cart').offset().left,
                        width: '10px',
                        height: '10px',
                        opacity: 0.7
                    }, 700, function() {
                        $(this).remove();
                        cart_total.html(response.data.total);
                        cart_count.attr('data-count', response.data.count).hide().show();
                    });
                    
                    var $fcd = f.closest('li').find('.flying-cart-data').data();
                    if($fcd){
                        var $item = $('#flying-cart li[data-id="'+response.data.item_id+'"]');
                        var cnt = parseInt(f.find('input[name="quantity"]').val()) || 1;
                        
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
                }
            } else if (response.status == 'fail') {
                alert(response.errors);
            }

        }, "json");
        return false;
    });


    //PRODUCT FILTERING
    var f = function () {

        var ajax_form_callback = function (f) {
            var fields = f.serializeArray();
            var params = [];
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].value !== '') {
                    params.push(fields[i].name + '=' + fields[i].value);
                }
            }
            var url = '?' + params.join('&');
            $(window).lazyLoad && $(window).lazyLoad('sleep');
            $('#product-list').html('<img src="' + f.data('loading') + '">');
            $.at.o.a(f);
            $.get(url+'&_=_', function(html) {
                var tmp = $('<div></div>').html(html);
                $('#product-list').html(tmp.find('#product-list').html());
                if (!!(history.pushState && history.state !== undefined)) {
                    window.history.pushState({}, '', url);
                }
                $(window).lazyLoad && $(window).lazyLoad('reload');
                $.at.o.r(f);
            });
        };

        $('.filters.ajax form input').change(function () {
            //if ( MatchMedia("only screen and (min-width: 993px)") ) {
                ajax_form_callback($(this).closest('form'));
            //}
        });
        $('.filters.ajax form').submit(function () {
            //if ( MatchMedia("only screen and (max-width: 992px)") ) {
            //    return true;
            //}
            ajax_form_callback($(this));
            return false;
        });

        $('.filters .filter-slider-wrapper').each(function () {
            if (!$(this).find('.filter-slider').length) {
                $(this).append('<div class="filter-slider"></div>');
            } else {
                return;
            }
            var min = $(this).find('.min');
            var max = $(this).find('.max');
            var min_value = parseFloat(min.attr('placeholder'));
            var max_value = parseFloat(max.attr('placeholder'));
            var step = 1;
            var slider = $(this).find('.filter-slider');
            if (slider.data('step')) {
                step = parseFloat(slider.data('step'));
            } else {
                var diff = max_value - min_value;
                if (Math.round(min_value) != min_value || Math.round(max_value) != max_value) {
                    step = diff / 10;
                    var tmp = 0;
                    while (step < 1) {
                        step *= 10;
                        tmp += 1;
                    }
                    step = Math.pow(10, -tmp);
                    tmp = Math.round(100000 * Math.abs(Math.round(min_value) - min_value)) / 100000;
                    if (tmp && tmp < step) {
                        step = tmp;
                    }
                    tmp = Math.round(100000 * Math.abs(Math.round(max_value) - max_value)) / 100000;
                    if (tmp && tmp < step) {
                        step = tmp;
                    }
                }
            }
            slider.slider({
                range: true,
                min: parseFloat(min.attr('placeholder')),
                max: parseFloat(max.attr('placeholder')),
                step: step,
                values: [parseFloat(min.val().length ? min.val() : min.attr('placeholder')),
                    parseFloat(max.val().length ? max.val() : max.attr('placeholder'))],
                slide: function( event, ui ) {
                    var v = ui.values[0] == $(this).slider('option', 'min') ? '' : ui.values[0];
                    min.val(v);
                    v = ui.values[1] == $(this).slider('option', 'max') ? '' : ui.values[1];
                    max.val(v);
                },
                stop: function (event, ui) {
                    min.change();
                }
            });
            min.add(max).change(function () {
                var v_min =  min.val() === '' ? slider.slider('option', 'min') : parseFloat(min.val());
                var v_max = max.val() === '' ? slider.slider('option', 'max') : parseFloat(max.val());
                if (v_max >= v_min) {
                    slider.slider('option', 'values', [v_min, v_max]);
                }
            });
        });
        
        $('.sidebar .filters .collapsible').click(function(){
            var self = $(this);
            
            var filters_expand = $.cookie('shop_filters_expand');
            if (filters_expand) {
                filters_expand = filters_expand.split(',');
            } else {
                filters_expand = [];
            }
            var i = $.inArray($(this).data('feature') + '', filters_expand);
            if (self.hasClass('expand')) {
                if (i != -1) {
                    filters_expand.splice(i, 1);
                }
            } else {
                if (i == -1) {
                    filters_expand[filters_expand.length] = $(this).data('feature');
                }
            }
            if (filters_expand) {
                $.cookie('shop_filters_expand', filters_expand.join(','), { expires: 30, path: '/'});
            } else {
                $.cookie('shop_filters_expand',  '', { expires: 0, path: '/'});
            }
            
            //animate collapse
            self.next().slideToggle(300, function(){
                self.toggleClass('expand');
            });
            
            //return false;
        });
        
        $('.content .filters .collapsible').click(function(){
            var self = $(this);
            if ( !self.hasClass('active') ) {
                self.closest('.filters').find('.collapsible').removeClass('active');
            }
            self.toggleClass('active');
            
            //return false;
        });
        
        $('#clear-filters-expand').click(function(){
            $.cookie('shop_filters_expand',  '', { expires: 0, path: '/'});
            //return false;
        });
    };
    f();
    
    // EXPANDABLE
    $('#page-content').off('mouseenter.expandablei').on('mouseenter.expandablei', '.thumbs.expandable .pl-item-image', function(){
        if ( MatchMedia("only screen and (max-width: 992px)") ) {
            return false;
        }
            
        var ib = $(this).find('img').height() + 20;
        var pb = $(this).parent().innerHeight();
        var ii = $(this).next('.pl-item-info').height();
        
        pb = pb - ii;
        if (ib > pb) {
            var ob = $(this).parent().find('.offers').innerHeight();
            ib = ib - pb;
            if (ib > ob) {
                ib = ob;
            }
            $(this).next('.pl-item-info').css('marginBottom', -ib);
        }
        
        return false;
    });
    
    $('#page-content').off('mouseleave.expandablei').on('mouseleave.expandablei', '.thumbs.expandable .pl-item-image', function(){
        $(this).next('.pl-item-info').removeAttr('style');
        return false;
    });
    
    $('#page-content').off('mouseenter.expandablepi').on('mouseenter.expandablepi', '.thumbs.expandable .pl-item-info', function(){
        if ( MatchMedia("only screen and (max-width: 992px)") ) {
            return false;
        }
        
        var pb = $(this).parent().height(), ob = $(this).find('.offers').innerHeight();
        $(this).find('.pl-item-info-expandable').css('maxHeight', pb - ob + 10);
        return false;
    });
    $('#page-content').off('mouseleave.expandablepi').on('mouseleave.expandablepi', '.thumbs.expandable .pl-item-info', function(){
        $(this).find('.pl-item-info-expandable').removeAttr('style');
        return false;
    });
    
    //ADDITION
    $.at.addition.init();
    
    //HIDE LARGE DESCRIPTION
    if ( $.at.shop.description_cut ) {
        $('.category-description').collapsibleDesc({
            collapse_height: parseInt($.at.shop.description_cut),
            expand_lbl: $.at.t('Expand description'),
            collapse_lbl: $.at.t('Collapse description')
        });
    }
    
    //autofit for jQuery UI Autocomplete 1.8.2!
    $("#search.autofit").each(function(){
        var self = $(this);
        self.autocomplete({
            delay: 500,
            minLength: 3,
            search: function(event, ui) {
                if($(this).val().replace(/^\s+|\s+$/g, '').length < 3){
                    $(this).autocomplete("close");
                    return false;
                }
            },
            source: function(request, response){
                request.term = request.term.replace(/^\s+|\s+$/g, '');
                var query = request.term.replace(/\s+/g, '+');
                $.ajax({
                    url: $.shop.url+'search/?query='+encodeURIComponent(query),
                    type: "GET",
                    dataType: "html",
                    success: function(data){
                        var items = $.map($(data).find('.product-list li:lt('+parseInt($.at.shop.autofit)+')'), function(item){
                            var regexp = new RegExp("(" + request.term.replace(/\s+/, "|", 'g') +")", "ig");
                            var name = $(item).find('h5').text();
                            return {
                                label: name,
                                value: name,
                                url: $(item).find('h5').parent().attr('href'),
                                text: '<img width="48" height="48" src="'+$(item).find('img').attr('src').replace(/^(.*\/[0-9]+\.)(.*)(\..*)$/, '$1' + '96x96' + '$3')+'" alt=""><span class="autofit-name">'+name.replace(regexp, '<span class="match">$1</span>')+'</span><span class="autofit-price">'+$(item).find('.price-wrapper').html()+'</span>'
                            }
                        });
                        if($(data).find('.product-list li').length > parseInt($.at.shop.autofit)) items[items.length] = {
                            label: ''+query,
                            value: ''+query,
                            url: $.shop.url+'search/?query='+encodeURIComponent(query),
                            text: $.at.t('Show more')
                        }
                        response(items);
                    }
                });
            },
            select: function( event, ui ) {
                location.href = ui.item.url;
                //return false;
            }
        }).data("autocomplete")._renderMenu = function( ul, items ) {
            //var _width = Math.max(self.innerWidth()+30, 200);
            var _width = self.innerWidth()+30;
            ul.addClass('autofit-product');
            
            $.each( items, function( index, item ) {
    			$('<li style="width: '+_width+'px;"></li>')
                    .data('item.autocomplete', item)
                    .append('<a href="'+item.url+'">'+item.text+'</a>')
                    .appendTo(ul);
    		});
        };
        $(window).bind('resize', function(){ self.autocomplete("close"); });
    });
    
    //LAZYLOADING
    if ($.fn.lazyLoad) {
        var paging = $('.lazyloading-paging');
        if (!paging.length) {
            return;
        }

        var times = parseInt(paging.data('times'), 10);
        var link_text = paging.data('linkText') || 'Load more';
        var loading_str = paging.data('loading-str') || 'Loading...';

        // check need to initialize lazy-loading
        var current = paging.find('li.selected');
        if (current.children('a').text() != '1') {
            return;
        }
        paging.hide();
        var win = $(window);

        // prevent previous launched lazy-loading
        win.lazyLoad('stop');

        // check need to initialize lazy-loading
        var next = current.next();
        if (next.length) {
            win.lazyLoad({
                container: '#product-list .product-list',
                load: function () {
                    win.lazyLoad('sleep');

                    var paging = $('.lazyloading-paging').hide();

                    // determine actual current and next item for getting actual url
                    var current = paging.find('li.selected');
                    var next = current.next();
                    var url = next.find('a').attr('href');
                    if (!url) {
                        win.lazyLoad('stop');
                        return;
                    }

                    var product_list = $('#product-list .product-list');
                    var loading = paging.parent().find('.loading').parent();
                    if (!loading.length) {
                        loading = $('<div class="align-center"><i class="icon16 loading"></i>'+loading_str+'</div>').insertBefore(paging);
                    }

                    loading.show();
                    $.get(url, function (html) {
                        var tmp = $('<div></div>').html(html);
                        if ($.Retina) {
                            tmp.find('#product-list .product-list img').retina();
                        }
                        product_list.append(tmp.find('#product-list .product-list').children());
                        var tmp_paging = tmp.find('.lazyloading-paging').hide();
                        paging.replaceWith(tmp_paging);
                        paging = tmp_paging;

                        times -= 1;

                        // check need to stop lazy-loading
                        var current = paging.find('li.selected');
                        var next = current.next();
                        if (next.length) {
                            if (!isNaN(times) && times <= 0) {
                                win.lazyLoad('sleep');
                                if (!$('.lazyloading-load-more').length) {
                                    $('<div class="align-center"><a href="#" class="lazyloading-load-more button">' + link_text + '</a></div>').insertAfter(paging)
                                        .click(function () {
                                            loading.show();
                                            times = 1;      // one more time
                                            win.lazyLoad('wake');
                                            win.lazyLoad('force');
                                            return false;
                                        });
                                }
                            } else {
                                win.lazyLoad('wake');
                            }
                        } else {
                            win.lazyLoad('stop');
                            $('.lazyloading-load-more').hide();
                        }

                        loading.hide();
                        tmp.remove();
                    });
                }
            });
        }
    }

});

var scrollImage = function(current, images, pos){
    var i = $.inArray(current, images);
    
    if (pos == 'prev' && i == 0) {
        return images[images.length - 1];
    } else if (pos == 'prev') {
        return images[i - 1];
    } else if (pos == 'next' && i == images.length - 1) {
        return images[0];
    } else if (pos == 'next') {
        return images[i + 1];
    }
    
    return '';
};

var newItem = function($item) {
    return  '<li data-id="' + $item.id + '">' +
                '<div class="flying-cart-img">' +
                    '<a href="' + $item.url + '" title="' + $item.name + '">' +
                        '<img src="' + ($item.img_url || $.at.urls.dummy["96"]) + '" alt="' + $item.name + '">' +
                    '</a>' +
                '</div>' +
                '<div class="flying-cart-offer align-left">' +
                    '<a href="' + $item.url + '">' + $item.name + '</a>&nbsp;<span class="gray">' + (typeof $item.sku_name !== 'undefined' ? $item.sku_name : "") + '</span>' +
                    '<span class="flying-cart-price nowrap">' +
                        (typeof $item.compare_price !== 'undefined' ? '<span class="hint">' + $item.compare_price + '</span>' : "") +
                        '<span class="price nowrap">' + $item.price + '</span>' +
                    '</span>' +
                '</div>' +
                '<div class="flying-cart-quantity">' +
                  	'<input type="text" value="' + $item.cnt + '" class="flying-cart-qty"> ' + $.at.t('pcs.') +
                '</div>' +
                '<div class="flying-cart-delete">' +
                    '<a href="#" class="flying-cart-del" title="' + $.at.t('Remove from cart') + '" rel="nofollow"><i class="material-icons mi-2x">&#xE5CD;</i></a>' +
                '</div>' +
            '</li>';
};