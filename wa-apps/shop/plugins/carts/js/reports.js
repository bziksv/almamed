( function ($) {
    $.storage = new $.store();
    $.reports = {
        init: function (options) {
            var that = this;
            if (typeof($.History) != "undefined") {
                $.History.bind(function () {
                    that.dispatch();
                });
            }
            $.wa.errorHandler = function (xhr) {
                if ((xhr.status === 403) || (xhr.status === 404) ) {
                    $("#s-content").html('<div class="content left200px"><div class="block double-padded">' + xhr.responseText + '</div></div>');
                    return false;
                }
                return true;
            };
            var hash = window.location.hash;
            if (hash === '#/' || !hash) {
                this.dispatch();
            } else {
                $.wa.setHash(hash);
            }
            document.documentElement.setAttribute('lang', options.lang);
            $.reports.initTimeframeSelector();
        },

        // Timeframe selector logic
        initTimeframeSelector: function() {
            var wrapper = $('#reportsmenu .s-reports-timeframe');
            var visible_option = wrapper.children('.s-reports-timeframe-dropdown').children('a');
            var custom_wrapper = wrapper.children('.s-custom-timeframe').hide();

            // Helper to get timeframe data from <li> element
            var getTimeframeData = function(li) {
                return {
                    timeframe: (li && li.data('timeframe')) || 30
                };
            };

            // Helper to set active timeframe <li>
            var setActiveTimeframe = function(li) {
                visible_option.find('b i').text(li.text());
                li.addClass('selected').siblings('.selected').removeClass('selected');
                var tf = getTimeframeData(li);
                if (tf.timeframe != 'custom') {
                    $.storage.set('shop/reports/timeframe', tf);
                }
            }

            // Helper to set up custom period selector
            var initCustomSelector = function() {

                var inputs = custom_wrapper.find('input');
                var from = inputs.filter('[name="from"]');
                var to = inputs.filter('[name="to"]');

                // One-time initialization
                (function() {
                    var updatePage = function() {
                        var from_date = from.datepicker('getDate');
                        var to_date = to.datepicker('getDate');
                        if (!from_date || !to_date) {
                            return false;
                        }
                        $.storage.set('shop/reports/timeframe', {
                            timeframe: 'custom',
                            from: Math.floor(from_date.getTime() / 1000),
                            to: Math.floor(to_date.getTime() / 1000)
                        });
                        $('#reportscontent').html('<div class="double-padded block"><i class="icon16 loading"></i></div>');
                        $.reports.dispatch();
                    };

                    // Datepickers
                    inputs.datepicker().change(updatePage).keyup(function(e) {
                        if (e.which == 13 || e.which == 10) {
                            updatePage();
                        }
                    });
                    inputs.datepicker('widget').hide();
                })();

                // Code to run each time 'Custom' is selected
                initCustomSelector = function() {
                    // Set datepicker values depending on previously selected options
                    var tf = $.reports.getTimeframe();
                    if (tf.timeframe == 'custom') {
                        from.datepicker('setDate', tf.from ? new Date(tf.from*1000) : null);
                        to.datepicker('setDate', tf.to ? new Date(tf.to*1000) : null);
                    } else if (tf.timeframe == 'all') {
                        from.datepicker('setDate', null);
                        to.datepicker('setDate', null);
                    } else {
                        from.datepicker('setDate', '-'+parseInt(tf.timeframe, 10)+'d');
                        to.datepicker('setDate', new Date());
                    }
                };
                initCustomSelector();
            };

            // Change selection when user clicks on dropdown list item
            wrapper.children('.s-reports-timeframe-dropdown').on('click', 'ul li:not(.selected)', function() {
                var li = $(this);
                var tf = getTimeframeData(li);
                if (tf.timeframe == 'custom') {
                    custom_wrapper.show();
                    initCustomSelector();
                    setActiveTimeframe(li);
                } else {
                    custom_wrapper.hide();
                    setActiveTimeframe(li);
                    $('#reportscontent').html('<div class="double-padded block"><i class="icon16 loading"></i></div>');
                    $.reports.dispatch();
                }
            });

            // Initial selection in dropdown menu
            var timeframe = $.storage.get('shop/reports/timeframe') || getTimeframeData(wrapper.find('ul li[data-default-choice]:first'));
            if (timeframe.timeframe == 'custom') {
                // Delay initialization to allow datepicker locale to set up properly.
                // Kinda paranoid, but otherwise localization sometimes fail in FF.
                $(function() {
                    setTimeout(function() {
                        custom_wrapper.show();
                        initCustomSelector();
                        setActiveTimeframe(wrapper.find('ul li[data-timeframe="custom"]'));
                    }, 100);
                });
            } else {
                wrapper.find('ul li').each(function() {
                    var li = $(this);
                    var tf = getTimeframeData(li);
                    if (tf.timeframe == timeframe.timeframe) {
                        setActiveTimeframe(li);
                        timeframe = null;
                        return false;
                    }
                });
                if (timeframe) {
                    setActiveTimeframe(wrapper.find('ul li:first'));
                }
            }
        },

        dispatch: function (hash) {
            if (hash === undefined) {
                hash = window.location.hash;
            }
            hash = hash.replace(/(^[^#]*#\/*|\/$)/g, ''); /* fix syntax highlight*/
            var original_hash = this.hash
            this.hash = hash;
            if (hash) {
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for (var i = 0; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
                                actionName += h.substr(0,1).toUpperCase() + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    var attr = hash.slice(attrMarker);
                    this.preExecute(actionName, attr);
                    if (typeof(this[actionName + 'Action']) == 'function') {
                        $.shop.trace('$.reports.dispatch',[actionName + 'Action',attr]);
                        this.setActiveTop(actionName);
                        this[actionName + 'Action'].apply(this, attr);
                    } else {
                        $.shop.error('Invalid action name:', actionName+'Action');
                    }
                    this.postExecute(actionName, attr);
                } else {
                    this.preExecute();
                    this.defaultAction();
                    this.postExecute();
                }
            } else {
                this.preExecute();
                this.defaultAction();
                this.postExecute();
            }
        },

        preExecute: function () {
            var $h1 = $('h1');
            $('body > .dialog').trigger('close').remove();
            if(!$h1.find('.loading:visible').length) {
                $h1.append('<i class="icon16 loading"></i>');
            }
        },

        postExecute: function () {
            $('#s-reports-custom-controls').empty();
        },

        setActiveTop: function (action) {
            if (!action) {
                action = 'sales';
            }
            var hash = '#/' + action + '/';
            var $li = $('ul.s-reports a[href="' + hash + '"]').parent('li').addClass('selected');
            $li.length && $li.siblings().removeClass('selected');
        },

        defaultAction: function () {
            var hash = $('#reportsmenu').find('.s-reports > li > a:first').attr('href');
            this.dispatch(hash);
        },

        parseParams: function (params) {
            var map = { };
            var sort = 0;
            $.each((params || '').split('&'), function (i, val) {
                val = (val || '');
                var exp = val.split('=');
                var left = exp[0] || '';
                var right = exp[1] || '';
                if (left) {
                    map[left] = {
                        value: right,
                        sort: sort++
                    };
                }
            });
            return map;
        },

        unparseParams: function (map) {
            var params_ar = $.map(map, function (item, key) {
                if (key && item !== undefined) {
                    var sort = 0, value = '';
                    if ($.isPlainObject(item)) {
                        sort = parseInt(item.sort, 10) || 0;
                        value = '' + (item.value || '');
                    } else {
                        value = '' + (item || '');
                    }
                    return { key: key, value: value, sort: sort };
                }
            });
            params_ar = params_ar.sort(function (a, b) {
                return a.value === 'type' && (a.sort > b.sort || a.value > b.value);
            });
            return $.map(params_ar, function (item) { return item.key + '=' + item.value; }).join('&');
        },

        cartsAction: function() {
            this.setActiveTop('carts');
            var url = '?plugin=carts&module=report&action=carts'+this.getTimeframeParams();
            $("#reportscontent").load(url, this.initCartsAction);
        },

        cartsAllAction: function() {
            this.setActiveTop('carts');
            var url = '?plugin=carts&module=report&action=carts&hash=all'+this.getTimeframeParams();
            $("#reportscontent").load(url, this.initCartsAction);
        },

        cartsSearchAction: function(query) {
            this.setActiveTop('carts');
            if(!query) {
                window.location.href = '?plugin=carts&module=report#/carts/';
                return;
            }
            var url = '?plugin=carts&module=report&action=carts&hash=search/'+query+this.getTimeframeParams();
            $("#reportscontent").load(url, this.initCartsAction);
        },

        initCartsAction: function() {
            var $f = $('#carts-search');
            $f.submit(function (e) {
                e.preventDefault();
                var v = $f.find('input').val();
                if(window.encodeURIComponent) {
                    v = encodeURIComponent(v);
                }
                window.location.href = $f.prop('action')+v;
            }).find('input').focus(function () {
                $(this).addClass('focus');
            }).blur(function () {
                if(!$(this).val()) {
                    $(this).removeClass('focus');
                }
            });

            $('#reportscontent').on('click', '#carts-pagination a', function(e){
                e.preventDefault();
                $('#reportscontent').load($(this).prop('href'));
            });
        },

        cartsIdAction: function(id) {
            this.setActiveTop('carts');
            $("#reportscontent").load('?plugin=carts&module=report&action=messages&id='+id);
        },

        usageAction: function() {
            this.setActiveTop('usage');
            $("#reportscontent").load('?plugin=carts&module=report&action=usage'+this.getTimeframeParams());
        },

        // Helper
        getTimeframe: function() {
            var result = $.storage.get('shop/reports/timeframe') || {
                timeframe: 90
            };

            var $storefront_selector = $('#s-reports-custom-controls .storefront-selector');
            if ($storefront_selector.length && $storefront_selector.val()) {
                result.sales_channel = $storefront_selector.val();
            }

            return result;
        },
        // Helper
        getTimeframeParams: function() {
            return '&' + $.param(this.getTimeframe());
        },

        // Helper to sort the table by one of the columns
        sortTable: function($th, asc) {
            var col_index = $th.index();
            var $table = $th.closest('table');
            var $tbody = $table.children('tbody');

            // Detach all rows
            var $trs = $tbody.children().detach();

            // Prepare objects for faster sorting
            var sort_as_strings = false;
            var trs = $trs.map(function(i, tr) {
                var $tr = $(tr);
                var $td = $tr.children().eq(col_index);
                var data = $td.data('sort');
                var value;
                if (data !== undefined) {
                    value = parseFloat(data);
                    if (isNaN(value)) {
                        value = data;
                        sort_as_strings = true;
                    }
                }
                if (value === undefined) {
                    value = $.trim($tr.text());
                    sort_as_strings = true;
                }
                return {
                    tr: tr,
                    value: value
                };
            }).get();

            // Sort
            if (sort_as_strings) {
                trs.sort(function(a, b) {
                    if (a.value > b.value) {
                        return asc ? -1 : 1;
                    }
                    if (a.value < b.value) {
                        return asc ? 1 : -1;
                    }
                    return 0;
                });
            } else {
                trs.sort(function(a, b) {
                    if (asc) {
                        return a.value - b.value;
                    } else {
                        return b.value - a.value;
                    }
                });
            }

            // Attach rows back to DOM
            $tbody.append($.map(trs, function(o) {
                return o.tr;
            }));
        }

    }
})(jQuery);