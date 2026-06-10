( function ($, undefined) {
    
    function a (element, options) {
        this.$e = $(element);
        this.$ul = $('<ul/>');
        this.$e.after(this.$ul);
        this.$ul.attr('class', this.$e.attr('class'));
        this.options = $.extend({}, a.default, options);
        this.getMenu();
        this.binds();
    };
    
    a.default = {
        icon: {},
        more: '<li class="parent"><a href="#" onclick="return false;">:name</a><ul></ul></li>',
        more_label: 'More'
    };
    
    a.prototype.getMenu = function () {
        var t = this;
        
        this.$ul.empty();
        
        var $parent = this.$ul.parent(),
            parent_width = $parent.width(),
            width = 0,
            $more_ul = $('<ul/>'),
            $more = $(this.options.more.replace(/:name/g, this.options.more_label)).append($more_ul);
            
        this.$ul.append($more);
        var more_width = $more.outerWidth();
        $more.remove();
        
        var flag = false,
            len = this.$e.children().length,
            i = 0;
        
        $.each(this.$e.children(), function (i, e) {
            var $li = $(e).clone();
            
            if ( flag ) {
                $more_ul.append($li);
                return true;
            }
            
            t.$ul.append($li);
            
            if ( ( width + $li.outerWidth() + more_width ) > parent_width && i < len ) {
                flag = true;
                $more_ul.append($li);
                return true;
            }
            
            width += $li.outerWidth();
        });
        
        if ( flag ) {
            this.$ul.append($more);
        }
        this.$e.trigger('responsive.menu.built');
    };
    
    a.prototype.binds = function () {
        $(window).on('resize.responsive.menu', $.proxy(this.getMenu, this));
        $(window).load($.proxy(this.getMenu, this));
    };
    
    $.fn.responsiveMenu = function(options) {
    	return this.each(function() {
    		if (!$(this).data('responsiveMenu')) {
    			$(this).data('responsiveMenu', new a(this, options));
    		}
    	});
    };
    $.fn.responsiveMenu.Constructor = a;
    
})(jQuery);

( function ($, undefined) {
    
    function a (element, options) {
		var t = this;
        t.$e = $(element);
        t.options = $.extend({}, a.default, options);
        t.init();
    };
    
    a.default = {		
		stylize_class: 'at-stylize'
	};
    
    a.prototype.init = function () {
        var t = this;
        if ( t.$e.hasClass(t.options.stylize_class + '-input') ) {
			return false;
		}
		
		t.$e.addClass(t.options.stylize_class + '-input');
		t.$p = t.$e.parent();
		
		if ( t.$p.is('label') ) {
			t.$p.addClass(t.options.stylize_class + '-label');
		} else {
			t.$p = $('<label class="' + t.options.stylize_class + '-label"></label>');			
			t.$e.wrap(t.$p);
		}
		
		t.$e.after('<span class="' + t.options.stylize_class + '-box"></span>');
    };    
    
    $.fn.stylizeInput = function(options) {
    	return this.each(function() {
    		if (!$(this).data('stylizeInput')) {
    			$(this).data('stylizeInput', new a(this, options));
    		}
    	});
    };
    $.fn.stylizeInput.Constructor = a;
    
})(jQuery);

// variable for event touch data
var UserTouch = ( function() {

    var min_touch_length = 5,
        touch_is_vertical,
        finger_place_x_start,
        finger_place_y_start,
        finger_place_x_end,
        finger_place_y_end,
        touch_delta_x,
        touch_delta_y,
        time_start,
        time_end,
        element;

    var on_touch_start = function(event) {

        finger_place_x_start = event.touches[0].pageX;
        finger_place_y_start = event.touches[0].pageY;
        finger_place_x_end = null;
        finger_place_y_end = null;
        touch_delta_x = null;
        touch_delta_y = null;
        touch_is_vertical = false,
            time_start = ( new Date() ).getTime(),
            time_end = false;

        UserTouch = {
            offsetStart: {
                top: finger_place_y_start,
                left: finger_place_x_start
            },
            offsetEnd: {
                top: false,
                left: false
            },
            offsetDelta: {
                x: false,
                y: false
            },
            orientation: {
                x: false,
                y: false
            },
            touchTime: false
        };

        element.addEventListener("touchmove", on_touch_move, false);
        element.addEventListener("touchend", on_touch_end, false);
    };

    var on_touch_move = function(event) {
        time_end = (new Date()).getTime();
        finger_place_x_end = event.touches[0].pageX;
        finger_place_y_end = event.touches[0].pageY;
        touch_delta_x = finger_place_x_end - finger_place_x_start;
        touch_delta_y = finger_place_y_end - finger_place_y_start;

        var is_horizontal = ( Math.abs(touch_delta_x) > Math.abs(touch_delta_y) && Math.abs(touch_delta_x) > min_touch_length );
        if (is_horizontal) {
            event.preventDefault();
        }

        UserTouch.offsetEnd = {
            top: finger_place_y_end,
            left: finger_place_x_end
        };

        UserTouch.offsetDelta = {
            x: touch_delta_x,
            y: touch_delta_y
        };

        if ( Math.abs(touch_delta_y) > min_touch_length ) {
            if ( touch_delta_y < 0 ) {
                UserTouch.orientation.y = "top";
            } else {
                UserTouch.orientation.y = "bottom";
            }
        }

        if ( Math.abs(touch_delta_x) > min_touch_length ) {
            if ( touch_delta_x < 0 ) {
                UserTouch.orientation.x = "left";
            } else {
                UserTouch.orientation.x = "right";
            }
        }

        UserTouch.touchTime = (time_end - time_start);
    };

    var on_touch_end = function() {
        // отключаем обработчики
        element.removeEventListener("touchmove", on_touch_move);
        element.removeEventListener("touchend", on_touch_end);
    };

    var bindEvents = function() {
        element = document.body;
        element.addEventListener("touchstart", on_touch_start, false);
    };

    document.addEventListener("DOMContentLoaded", function() {
        bindEvents();
    });

    return {
        offsetStart: {
            top: false,
            left: false
        },
        offsetEnd: {
            top: false,
            left: false
        },
        offsetDelta: {
            x: false,
            y: false
        },
        orientation: {
            x: false,
            y: false
        },
        touchTime: false
    };

})();

// Mobile Menu JS
( function($) {
    
    var storage = {
        activeMenuClass: function() {
            var classes = [];
    
            $('.mobile-nav-button.action').each(function(){
                var nav = $(this).data('nav');
                classes.push('nav-' + nav + '-open');
            });
            
            return classes;
        },
        swipeTime: 300,
        swipe_horizontal_percent: 35,
        getWrapper: function() {
            return $("body");
        },
        menu_is_active: function() {
            var is_active = false,
                classes = this.activeMenuClass();
            
            for (var i = 0; i < classes.length; i++) {
                is_active = ( is_active || this.getWrapper().hasClass( classes[i] ) );
            }
            return is_active;
        }
    };

    // Обработчики кликов
    var bindEvents = function() {
        // Открываем меню
        $(document).on( "click", ".mobile-nav-button.action", function() {
            showHiddenMenu(this);
            return false;
        });

        // Закрываем меню
        $(".mobile-nav-wrapper").on( "click", function() {
            hideHiddenMenu();
            return false;
        });

        // Блокируем всплытие кликов у меню-контейнера
        $(".mobile-nav-block-wrapper").on( "click", function(event) {
            event.stopPropagation();
        });

        // Клик по ссылке в меню
        $(".mobile-nav-block-wrapper").on( "click", "a", function() {
            onMenuClick( $(this) );
            return false;
        });

        var $body = document.body,
            $link = document.querySelectorAll(".mobile-nav-button.action");

        $body.addEventListener("touchend", onTouchEndController, false);
        $.each($link, function(){
            this.addEventListener("touchend", onMenuTouchEnd, false);
        });
    };

    var onTouchEndController = function(event) {
        var cancelTargetClass = [];

        var checkTargetClass = function($target, elementClass) {
            var result;

            if ( $target.hasClass(elementClass) ) {
                result = $target;

            } else if ( $target.closest("." + elementClass).length ) {
                result = $target.closest("." + elementClass);

            } else {
                result = false;
            }

            return result;
        };

        var is_passed = true;
        for (var i in cancelTargetClass ) {
            if (cancelTargetClass.hasOwnProperty(i)) {
                if ( checkTargetClass( $(event.target), cancelTargetClass[i]) ) {
                    is_passed = false;
                }
            }
        }

        if (is_passed) {
            onTouchEnd();
        }
    };

    // Клик по ссылке в меню
    var onMenuClick = function(selector) {
        var link_href = selector.attr("href"),
            menu_animate_time,
            animation_time;

        // Вычисляем время
        menu_animate_time = parseFloat( $(".mobile-nav-wrapper").css("transition-duration") ) * 1000;
        animation_time = menu_animate_time || 300;

        // Выполняем редирект после закрытия меню
        if (link_href) {

            // Скрываем меню
            hideHiddenMenu();

            // Если URL отличается от текущего, то редирект
            if ( location.pathname !== link_href ) {
                // Выполняем редирект после закрытия меню
                setTimeout( function() {
                    location.href = link_href;
                }, animation_time);
            }
        }
    };

    var onTouchEnd = function() {
        var menu_is_active = storage.menu_is_active(),
            orientation_x = (UserTouch.orientation.x),
            is_swipe = ( storage.swipeTime >= UserTouch.touchTime ),
            is_horizontal_swipe = false;

        if (is_swipe) {
            is_horizontal_swipe = ( Math.abs( parseInt( ( UserTouch.offsetDelta.y / UserTouch.offsetDelta.x ) * 100 ) ) <= storage.swipe_horizontal_percent );
            if (is_horizontal_swipe) {
                if (orientation_x === "left" && menu_is_active) {
                    hideHiddenMenu();
                }
            }
        }
    };

    var onMenuTouchEnd = function() {
        var menu_is_active = storage.menu_is_active(),
            orientation_x = (UserTouch.orientation.x),
            is_swipe = ( storage.swipeTime >= UserTouch.touchTime ),
            is_horizontal_swipe = false;

        if (is_swipe) {
            is_horizontal_swipe = ( Math.abs( parseInt( ( UserTouch.offsetDelta.y / UserTouch.offsetDelta.x ) * 100 ) ) <= storage.swipe_horizontal_percent );
            if (is_horizontal_swipe) {
                if (orientation_x === "right" && !menu_is_active) {
                    showHiddenMenu(this);
                }
            }

        }
    };

    // Показать скрытое меню
    var showHiddenMenu = function(el) {
        var nav = $(el).data('nav');
        $("body").addClass('nav-' + nav + '-open');
    };

    // Скрыть скрытое меню
    var hideHiddenMenu = function() {
        $("body").removeClass(storage.activeMenuClass().join(' '));
    };

    // Вызов
    $(document).ready( function() {
        bindEvents();
    });

})(jQuery);

var MatchMedia = function( media_query ) {
    var matchMedia = window.matchMedia,
        is_supported = (typeof matchMedia === "function");
    if (is_supported && media_query) {
        return matchMedia(media_query).matches
    } else {
        return false;
    }
};

var scrollbarWidth = function() {
    var block = $('<div>').css({'height':'50px','width':'50px'}),
        indicator = $('<div>').css({'height':'200px'});

    $('body').append(block.append(indicator));
    var w1 = $('div', block).innerWidth();    
    block.css('overflow-y', 'scroll');
    var w2 = $('div', block).innerWidth();
    $(block).remove();
    return (w1 - w2);
}

//SHOW MORE TAGS .etc
;(function($, window, document, undefined) {

    function showMore(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, showMore.Defaults, options);
        
        this.setup();
        $(window).on('resize', $.proxy(function(){
            window.clearTimeout(this.resizeTimer);
    		this.resizeTimer = window.setTimeout($.proxy(function(e) { this.onResize(e); }, this), this.options.responsive_refresh_rate);
        }, this));
    };
    
    showMore.Defaults = {
        show_per_click: 5,
        show_all: false,
        btn_lbl: 'More',
        responsive_refresh_rate: 200
    };
    
    showMore.prototype.setup = function() {
        this.$element.addClass('show-more-box').children().hide().addClass('show-more-item');
    	this.button = $('<a href="#" class="show-more-button button">' + this.options.btn_lbl + '</a>');
    	this.button.click($.proxy(function() {
    	    this.filter = (!!this.filter && this.options.show_all) ? '' : ':lt(' + this.options.show_per_click + ')';
    	    var group = this.$element.children(':hidden' + this.filter);
    	    group.each(function(i, e){
                var $e = $(e);
                //$e.show();
                setTimeout(function(){
                    $e.show().addClass('ready');
                }, i * 80);
            });
            setTimeout($.proxy(function(){
                if (!this.$element.children(':hidden').length) {
                    this.button.hide();
                }
            }, this), group.length * 80);
            return false;
    	}, this));
    	this.$element.after(this.button);
    	this.button.click();
    };
    
    showMore.prototype.destroy = function() {
        this.$element
            .removeClass('show-more-box')
            .children().removeClass('show-more-item ready')
                       .attr('style', '');
            this.button.remove();
            this.filter = null;
    };
    
    showMore.prototype.onResize = function() {
        if ( MatchMedia("only screen and (min-width: 993px)") ) {
            if( !this.$element.hasClass('show-more-box') ) {
                this.setup();
            }
        } else {
            this.destroy();
        }
    };
    
    $.fn.showMore = function(options) {
    	return this.each(function() {
    		if (!$(this).data('showMore')) {
    			$(this).data('showMore', new showMore(this, options));
    		}
    	});
    };
    $.fn.showMore.Constructor = showMore;

})(window.jQuery, window, document);

// CUT DESCRIPTION
;(function($, window, document, undefined) {
    
    function collapsibleDescription(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, collapsibleDescription.defaults, options);
        
        this.setup();
    };
    
    collapsibleDescription.defaults = {
        expand_lbl: 'Expand description',
        collapse_lbl: 'Collapse description',
        collapse_height: 100
    };
    
    collapsibleDescription.prototype.setup = function() {
        this.button = $('<a href="#" class="collapsible-description-button collapse">' + this.options.collapse_lbl + '</a>')
        
        //this._height = this.$element.innerHeight();
        this.$element.children().wrapAll('<div class="collapsible-description-wrapper"></div>');
        this.$wrapper = this.$element.children();
        
        if (this.options.collapse_height > this.$wrapper.height()) {
            //console.log('collapsibleDescription: height <');
            return false;
        }
        this.button.click($.proxy(function(){
            if (this.button.hasClass('collapse')) {
                this.$element.animate({ height: this.options.collapse_height }, 'slow');
                this.button.html(this.options.expand_lbl);
            } else {
                this.$element.animate({ height: this.$wrapper.height() }, 'slow');
                this.button.html(this.options.collapse_lbl);
            }
            this.button.toggleClass('collapse expand');
            this.$element.toggleClass('collapse expand');
            return false;
        }, this));
        this.$element.addClass('collapsible-description expand').append(this.button);
        this.button.click();
    };
    
    $.fn.collapsibleDesc = function(options) {
    	return this.each(function() {
    		if (!$(this).data('collapsibleDesc')) {
    			$(this).data('collapsibleDesc', new collapsibleDescription(this, options));
    		}
    	});
    };
    $.fn.collapsibleDesc.Constructor = collapsibleDescription;
    
})(window.jQuery, window, document);

( function ($, undefined) {
    
    var bindEvents = function () {

        $('.gps-bxslider').each(function(){
            var slider = $(this), 
                options = slider.data('options');
                
            slider.bxSlider(options);
            if ( options.auto ){
                slider.closest('.bx-wrapper').hover(function(){
                    slider.stopAuto(true);
                },function(){
                    slider.startAuto(true);
                });
            }
        });

    };
    
    $(document).ready(function () {
        
        if ( $('.gps-bxslider').length ) {
            $.at.pL({ label: 'jquery.bxslider', success: bindEvents });    
        }
        
    });
    
})(jQuery);

( function ($, undefined) {
    
    var bindEvents = function () {
        
        $('.gps-cslider').each(function(){
            var slider = $(this), 
                options = slider.data('options');
                
            slider.cslider(options);
            if ( options.hideControls ){
                slider.find('.da-arrows,.da-dots').hide();
            }
        });
        
    };
    
    $(document).ready(function () {
        
        if ( $('.gps-cslider').length ) {
            $.at.pL({ label: 'jquery.cslider', success: bindEvents });    
        }
        
    });
    
})(jQuery);

( function ($, undefined) {
    
    var bindEvents = function () {
        
        $(document).ready(function () {
        
            $(".fancybox").fancybox({
                closeBtn: false,
                helpers: {
        			title: { type : 'inside' },
        			buttons	: { skipSingle: true }
        		}
            });
            
        });
        
    };
    
    $.at.pL({ label: 'jquery.fancybox', success: bindEvents });
    //$.at.pL({ label: 'jquery.fancybox', success: function(){ console.log('sfgdgd'); } });
    
})(jQuery);

( function ($, undefined) {
    
    var bindEvents = function () {
        
        $('.gps-nivoslider').each(function(){
            var slider = $(this), 
                options = slider.data('options');
                
            slider.nivoSlider(options);
        });
        
    };
    
    $(window).load(function () {
        
        if ( $('.gps-nivoslider').length ) {
            $.at.pL({ label: 'jquery.nivoslider', success: bindEvents });    
        }
        
    });
    
})(jQuery);

$(document).ready(function() {
    $('input[type=checkbox],input[type=radio]').stylizeInput();
    
    $('<style type="text/css">.dialog-margin { overflow: hidden; margin-right: ' + scrollbarWidth() + 'px; }</style>').appendTo('head');
    
    $('.base-menu .parent > a').append('<button class="toggle-menu-child"><i class="material-icons mi-2x">&#xE5CF;</i></button>');
    $(document).on('click', '.toggle-menu-child', function(){
        var ul = $(this).closest('li.parent').find('ul:first');
        if (ul.is(':hidden')) {
            ul.slideDown();
            $(this).find('i').html('&#xE5CE;');
        } else {
            ul.slideUp();
            $(this).find('i').html('&#xE5CF;');
        }
        
        return false;
    });
    
    if ($.at.app.submenu_count) {
        $('.base-menu.type2 li.parent > a').each(function(){
            if ($(this).next().children('li').length > $.at.app.submenu_count) {
                var url = $(this).attr('href');
                $(this).next().append('<li class="submenu-show-all"><a href="' + url + '">' + $.at.t('Show more') + '</a></li>');
            }
        });
    }
    
    $('.base-menu.type3 .selected').parents('ul').each(function(){
        $(this).show().prev().find('i').html('&#xE5CE;');
    });
    $('.base-menu.type3 .selected').children('ul').show().prev().find('i').html('&#xE5CE;');
    /*
    $.each($.at.shop.category.images, function(i, e){
        if ( e.icon ) {
            $('[data-icon-id="' + i + '"]').prepend('<img class="plugin-imageincat-img" src="' + e.icon + '" alt="">');
        }
    });
    */
    if ( MatchMedia("only screen and (min-width: 993px)") ) {

        $('.base-menu.pages-top').responsiveMenu({
            more_label: $.at.t('More')
        });

        $('.base-menu.tree').responsiveMenu({
            more_label: '<i class="material-icons mi-lg pull-left">&#xE5D2;</i>' + $.at.t('More')
        });
        
        $('.sidebar .brands').showMore({
            show_all: true,
            btn_lbl: $.at.t('More')
        });
        
        $('.sidebar .tags').showMore({
            show_per_click: 20,
            btn_lbl: $.at.t('More')
        });
        
    };

    $(document).on('click.toggle_menu', '.toggle-menu', function(){
        $(this).parent().parent().find('.sidebar-box').slideToggle();
        return false;
    });
    
    $(window).resize(function(){
        if ( !(MatchMedia("only screen and (max-width: 992px)")) ) {
            $('.sidebar-box').attr('style', '');
            $('.base-menu ul').attr('style', '');
            $('.base-menu li li .material-icons').html('&#xE5CF;');
        }
    });
    
    //DIALOGS
    $(document).on('click.close_dialog', '.dialog', function(e){
        
        if ($(e.target).hasClass('dialog-window')) {
            $(this).closest('.dialog').hide().find('.cart').empty();
            $('body, #footer-pane').removeClass('dialog-margin');
        }
        
        //return false;
    });
    $('.dialog').on('click.close_dialog', 'a.dialog-close', function () {
    
        $(this).closest('.dialog').hide().find('.cart').empty();
        $('body, #footer-pane').removeClass('dialog-margin');
        
        return false;
        
    });
    $(document).on('keyup.close_dialog', function(e) {
    
        if (e.keyCode == 27) {
            $(".dialog:visible").hide().find('.cart').empty();
            $('body, #footer-pane').removeClass('dialog-margin');
        }
        
    });
    
    //AUTH
    var auth = function(url, data, type, submit){
        if (data == 'undefined') {
            data = {};
        }
        if (submit == 'undefined') {
            sumbit = false;
        }
        if (type == 'undefined') {
            type = 'GET';
        }
        var d = $('#dialog');
        var c = d.find('.cart');
        c.append('<i class="icon32 loading"></i>');
        d.show();
        
        $('body, #footer-pane').addClass('dialog-margin');
        
        $.ajax({
            url: url,
            type: type,
            data: data,
            success: function(response){
                var _tmp = $(response);
                c.html(_tmp.find('#page').html());
                c.prepend('<a href="#" class="dialog-close"><i class="material-icons mi-2x">&#xE5CD;</i></a>');
                
                if(submit && !c.find('.wa-error').length){
                    
                    $(document).off('keyup.close_dialog');
                    $(document).off('click.close_dialog');
                    $('.dialog').off('click.close_dialog').on('click.close_dialog', 'a.dialog-close', function(){
                        $(this).closest('.dialog').hide().find('.cart').empty();
                        $(this).attr('href', location.href);
                    });
                    
                    var text =  '<p>' +
                                    '<a href="' + $.at.urls.wa_url + '">' + $.at.t('Back to home page') + '</a> ' + 
                                    $.at.t('or') +
                                    ' <a href="' + location.href + '">' + $.at.t('back to current page') + '</a>' +
                                '</p>';
                    
                    if (!_tmp.find('#page').length) {
                        c.find('.loading').remove();
                        var text =  '<h1>' + $.at.t('Congratulations!') + '</h1>' +
                                    '<p>' + $.at.t('Authorization was successful!') + '</p>' + text;
                    }
                    
                    c.append(text);
                }
                
                c.find('form').attr('action', url).submit(function(){
                    var fields = $(this).serializeArray();
                	var params = {};
                	for (var i = 0; i < fields.length; i++) {
                		if (fields[i].value !== '') {
                		    params[fields[i].name] = ''+fields[i].value;
                		}
                	}
                	c.empty();
                	auth(url, params, 'POST', true);
                    
                    return false;
                });
                
                c.find('.wa-submit a, a.auth-action-link').click(function(){
                    c.empty();
                    auth($(this).attr('href'));
                    return false;
                });
                
                c.find('input[type=checkbox],input[type=radio]').stylizeInput();
            }
        });
    };
    if ( MatchMedia("only screen and (min-width: 993px)") ) {
        $('.authpopup').click(function(){
            auth($(this).attr('href'));
            
            return false;
        });
    };

    //BACK TO TOP
    $(window).scroll(function () {
	    if ( MatchMedia("only screen and (min-width: 993px)") ) {
    	    var wrapper = $("#back-top-wrapper");
    		if ($(this).scrollTop() > 100) {
    		    wrapper.fadeIn();
    		    //wrapper.addClass('active');
    		} else {
    		    wrapper.fadeOut();
    		    //wrapper.removeClass('active');
    		}
		}
	});
	$('#back-top').click(function () {
		$('body,html').animate({ scrollTop: 0 }, 800);
		return false;
	});
	
    // MAILER app email subscribe form
    $('#mailer-subscribe-form input.charset').val(document.charset || document.characterSet);
    $('#mailer-subscribe-form').submit(function() {
        var form = $(this);

        var email_input = form.find('input[name="email"]');
        var email_submit = form.find('input[type="submit"]');
        if (!email_input.val()) {
            email_input.addClass('error');
            return false;
        } else {
            email_input.removeClass('error');
        }
        
        email_submit.hide();
        email_input.addClass('loading');
        //email_input.after('<i class="icon16 loading"></i>');

        $('#mailer-subscribe-iframe').load(function() {
            email_input.removeClass('loading');
            $('#mailer-subscribe-form').hide();
            $('#mailer-subscribe-iframe').hide();
            $('#mailer-subscribe-thankyou').show();
        });
    });
    
    // FLYING CART
    if($.at.shop.flying_cart_item){
	    
        $("#flying-cart").off('click.flying-cart-del').on('click.flying-cart-del', '.flying-cart-del', function(){
            var li = $(this).closest('li');
            
            $.at.o.a($('#flying-cart'));
            $.post($.at.shop.url + 'cart/delete/', {html: 1, id: li.data('id')}, function (response) {
                li.animate({opacity: 0}, 'slow', function(){
                    
                    if (response.data.count == 0) {
                        //response.data.total = $.at.t('Empty');
                        $('#cart').addClass('empty');
                    }
                    $(".cart-total").html(response.data.total);
                    $(".cart-count").attr('data-count', response.data.count).hide().show();
                    
                    li.remove();
                    $.at.shop.setFlyingHeight();
                });
                $.at.o.r($('#flying-cart'));
            }, "json");
            return false;
        });
        
        $("#flying-cart").off('change.flying-cart-qty').on('change.flying-cart-qty', '.flying-cart-qty', function(){
            var self = $(this), li = self.closest('li');
            self.val(self.val() > 0 ? self.val() : 1);
            
            $.at.o.a($('#flying-cart'));
            $.post($.at.shop.url + 'cart/save/', {html: 1, id: li.data('id'), quantity: self.val()}, function (response) {
                li.find('.price').html(response.data.item_total);
                if (response.data.q) {
                    self.val(response.data.q);
                }
                if (response.data.error) {
                    alert(response.data.error);
                } else {
                    self.removeClass('error');
                }
                
                $(".cart-total").html(response.data.total);
                $(".cart-count").attr('data-count', response.data.count).hide().show();
                $.at.o.r($('#flying-cart'));
            }, "json");
        });
        if ($('#flying-cart').length) {
            $.at.shop.setFlyingHeight();
        }
        
    };
    
});