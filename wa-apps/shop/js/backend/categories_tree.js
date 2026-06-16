(function($) {

    // private helper methods in closure

    var getId = function(el) {
        var regexp = /^category-(.*?)-handler$/;
        return regexp.test(el.attr('id')) ?
                el.attr('id').replace(regexp, function() {
                    return parseInt(arguments[1], 10) || 0;
                }) : 0;
    };

    var getContext = function(el) {
        if (!getId(el)) {
            var p = el.parent();
            var t = $('#s-category-list-wrap');
            if (!t.length) {
                t = p.next();
            }
            var u = $('#s-category-list').find('ul:first');
        } else {
            var p = el.parents('li:not(.drag-newposition):first'), t = p.find('ul:first'), u = t;
        }
        return {
            parent: p,
            target: t,
            ul: u
        };
    };

    var onCollapse = function(el, func) {
        var context = getContext(el);
        if (context.parent.attr('data-type') == 'category' && !context.parent.hasClass('dynamic')) {
            context.parent.trigger('count_subtree', true);
        }
        el.removeClass('darr').addClass('rarr');
        context.target.hide();
        if (typeof func === 'function') {
            func(el);
        }
    };

    var onExpand = function(el, func) {
        var context = getContext(el);
        if (context.parent.attr('data-type') == 'category') {
            context.parent.trigger('count_subtree', false);
        }
        el.removeClass('rarr').addClass('darr');
        context.target.show();
        if (typeof func === 'function') {
            func(el);
        }
    };

    /**
     * @param context
     * @param {Boolean} status
     */
    var setLoadingIcon = function(context, status) {
        var icon = context.parent.find('.loading:first');
        var counters = context.parent.find('.counters');
        if (status) {
            icon.show();
            counters.hide();
        } else {
            icon.hide();
            counters.show();
        }
    };

    var collapse = function(el, func) {
        onCollapse(el, func);
        $.get('?action=categoryExpand&id=' + getId(el) + '&collapsed=1');
    };

    var expand = function(el, onExpandFunc, afterExpandFunc) {
        if (el.data('loading_content')) {
            return;
        }
        var context = getContext(el);
        if (!context.ul.length) {
            setLoadingIcon(context, true);
        } else {
            onExpand(el, onExpandFunc);
        }

        var loading_content = !context.ul.length;
        el.data('loading_content', loading_content);
        $.get('?action=categoryExpand&id=' + getId(el) + (loading_content ? '&tree=1' : ''),
            function(html) {
                if (loading_content) {
                    if (context.target.length) {
                        context.target.append(html);
                    } else {
                        context.parent.append(html);
                    }
                    setLoadingIcon(context, false);
                    onExpand(el, onExpandFunc);
                    el.data('loading_content', false);
                    if (typeof afterExpandFunc === 'function') {
                        afterExpandFunc();
                    }
                } else {
                    if (typeof afterExpandFunc === 'function') {
                        afterExpandFunc();
                    }
                }
            }
        );
    };

    var escapeHtml = function(str) {
        return $('<div>').text(str || '').html();
    };

    var shouldSkipCategoryScroll = function() {
        var hash = window.location.hash || '';
        var search = window.location.search || '';
        return /quickeditor=1(?:&|$)/.test(hash)
            || /(?:^|[?&])quickeditor=1(?:&|$)/.test(search);
    };

    var scrollCategoryIntoView = function($li) {
        if (!$li.length || shouldSkipCategoryScroll()) {
            return;
        }
        var $scrollContainer = $('#s-category-list-block');
        if ($scrollContainer.length && $scrollContainer.get(0).scrollHeight > $scrollContainer.innerHeight()) {
            var top = $li.offset().top - $scrollContainer.offset().top + $scrollContainer.scrollTop() - 24;
            $scrollContainer.animate({ scrollTop: Math.max(0, top) }, 200);
        } else if ($li.get(0).scrollIntoView) {
            $li.get(0).scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    };

    var expandPathStep = function(path, index, callback, retries) {
        retries = retries || 0;
        if (!path || index >= path.length) {
            callback();
            return;
        }

        var category_id = parseInt(path[index].id, 10);
        var $li = $('#category-' + category_id);
        if (!$li.length) {
            if (retries < 8 && index > 0) {
                setTimeout(function() {
                    expandPathStep(path, index, callback, retries + 1);
                }, 80);
                return;
            }
            callback();
            return;
        }

        var finishStep = function() {
            expandPathStep(path, index + 1, callback);
        };

        if (index < path.length - 1) {
            var handler = $('#category-' + category_id + '-handler');
            if (handler.length && handler.hasClass('rarr')) {
                expand(handler, null, finishStep);
            } else {
                finishStep();
            }
        } else {
            finishStep();
        }
    };

    $.categories_tree = {

        init: function() {
            $('#s-category-list-block').off('click', '.collapse-handler-ajax').on('click', '.collapse-handler-ajax', function() {
                var self = $(this);
                if (self.hasClass('darr')) {
                    collapse(self);
                } else {
                    expand(self);
                }
            });
            $('#s-category-list-block .heading').off('click').click(function(e) {
                var $collapse_handler = $(this).find('.collapse-handler-ajax');
                if (!$collapse_handler.is(e.target)) {
                    $collapse_handler.click();
                }
            });
            this.initCategorySearch();
            this.repairListDom();
        },

        updateCurrentPath: function(path) {
            var $el = $('#s-category-current-path');
            if (!$el.length || !path || !path.length) {
                $el.hide().empty();
                return;
            }

            var parts = [];
            $.each(path, function(i, item) {
                if (i > 0) {
                    parts.push('<span class="s-category-path-sep">›</span>');
                }
                if (i === path.length - 1) {
                    parts.push('<span class="s-category-path-item current">' + escapeHtml(item.name) + '</span>');
                } else {
                    parts.push('<a href="#/products/category_id=' + item.id + '&view=table" class="s-category-path-item">' + escapeHtml(item.name) + '</a>');
                }
            });
            $el.html(parts.join(' ')).show();
        },

        revealCategory: function(category_id, callback) {
            category_id = parseInt(category_id, 10) || 0;
            if (!category_id) {
                $('#s-category-current-path').hide().empty();
                callback && callback();
                return;
            }

            var self = this;
            var mainHandler = $('#s-category-list-handler');
            var categoryList = $('#s-category-list-wrap');

            var loadPath = function() {
                $.ajax({
                    url: '?module=products&action=categoryPath&id=' + category_id,
                    dataType: 'json',
                    global: false,
                    success: function(r) {
                        if (!r || r.status !== 'ok' || !r.data || !r.data.path) {
                            callback && callback();
                            return;
                        }

                        self.updateCurrentPath(r.data.path);
                        expandPathStep(r.data.path, 0, function() {
                            var $li = $('#category-' + category_id);
                            if ($li.length) {
                                scrollCategoryIntoView($li);
                            }
                            callback && callback($li);
                        });
                    }
                });
            };

            if (mainHandler.length && mainHandler.hasClass('rarr')) {
                expand(mainHandler, function() {
                    categoryList.show();
                    loadPath();
                });
            } else {
                categoryList.show();
                loadPath();
            }
        },

        initCategorySearch: function() {
            var $block = $('#s-category-list-block');
            if (!$block.length) {
                return;
            }

            var $input = $('#s-category-search');
            var $results = $('#s-category-search-results');
            if (!$input.length || !$results.length) {
                return;
            }

            if ($block.data('search-inited')) {
                return;
            }
            $block.data('search-inited', 1);

            var timer = null;
            var lastQuery = null;
            var xhr = null;

            var hideResults = function() {
                $results.hide().empty().removeClass('is-loading');
            };

            var runSearch = function(q) {
                if (xhr) {
                    xhr.abort();
                    xhr = null;
                }
                lastQuery = q;
                $results.html('<div class="s-category-search-empty hint">' + escapeHtml($_('Loading')) + '...</div>').addClass('is-loading').show();

                xhr = $.ajax({
                    url: '?module=products&action=categorySearch',
                    data: { q: q },
                    dataType: 'json',
                    global: false,
                    success: function(r) {
                        xhr = null;
                        if ($.trim($input.val()) !== q) {
                            return;
                        }
                        $results.removeClass('is-loading');
                        if (!r || r.status !== 'ok' || !r.data.categories || !r.data.categories.length) {
                            $results.html('<div class="s-category-search-empty hint">' + escapeHtml($_('No categories found')) + '</div>').show();
                            return;
                        }
                        var html = $.map(r.data.categories, function(item) {
                            return '<a href="#/products/category_id=' + item.id + '&view=table" class="s-category-search-item">' +
                                '<span class="s-category-search-name">' + escapeHtml(item.name) + '</span>' +
                                '<span class="s-category-search-path hint">' + escapeHtml(item.path) + '</span>' +
                            '</a>';
                        }).join('');
                        $results.html(html).show();
                    },
                    error: function() {
                        xhr = null;
                        if ($.trim($input.val()) !== q) {
                            return;
                        }
                        $results.removeClass('is-loading');
                        $results.html('<div class="s-category-search-empty hint">' + escapeHtml($_('No categories found')) + '</div>').show();
                    }
                });
            };

            $input.off('.sCategorySearch').on('input.sCategorySearch', function() {
                clearTimeout(timer);
                var q = $.trim($input.val());
                if (q.length < 2) {
                    hideResults();
                    lastQuery = null;
                    return;
                }
                if (q === lastQuery && $results.is(':visible') && $results.children().length) {
                    return;
                }
                timer = setTimeout(function() {
                    runSearch(q);
                }, 200);
            });

            $input.on('keydown.sCategorySearch', function(e) {
                if (e.which === 27) {
                    $input.val('');
                    hideResults();
                    lastQuery = null;
                }
            });

            $block.off('click.sCategorySearch').on('click.sCategorySearch', '.s-category-search-item', function() {
                $input.val('');
                hideResults();
                lastQuery = null;
            });

            $(document).off('click.sCategorySearch').on('click.sCategorySearch', function(e) {
                if (!$(e.target).closest('.s-category-sidebar-tools').length) {
                    $results.hide();
                }
            });
        },

        collapse: function(handler, func) {
            handler = $(handler);
            if (handler.hasClass('darr')) {
                collapse(handler, func);
            } else if (typeof func === 'function') {
                func(handler);
            }
        },

        expand: function(handler, onExpand, afterExpand) {
            handler = $(handler);
            if (handler.hasClass('rarr')) {
                expand(handler, onExpand, afterExpand);
            } else {
                if (typeof onExpand === 'function') {
                    onExpand(handler);
                }
                if (typeof afterExpand === 'function') {
                    afterExpand(handler);
                }
            }
        },

        isCollapsed: function(handler) {
            return $(handler).hasClass('rarr');
        },

        setExpanded: function(category_id) {
            $.get('?action=categoryExpand&id=' + category_id);
        },

        setCollapsed: function(category_id) {
            $.get('?action=categoryExpand&id=' + category_id + '&collapsed=1');
        },

        getHandlerByCategoryId: function(category_id) {
            var handler = $();
            category_id = parseInt(category_id, 10) || 0;
            if (!category_id) {
                handler = $('#s-category-list-handler');
            } else {
                handler = $('#category-' + category_id + '-handler');
            }
            return handler;
        },

        /**
         * Fix broken category tree DOM after drag-n-drop (li outside ul.menu-v, duplicate ids).
         */
        repairListDom: function() {
            var $root = $('#s-category-list');
            if (!$root.length || !$root.hasClass('s-collection-list')) {
                return;
            }

            var $rootUl = $root.children('ul.menu-v').first();
            if (!$rootUl.length) {
                $rootUl = $('<ul class="menu-v with-icons"></ul>').prependTo($root);
            }
            $root.children('li').appendTo($rootUl);

            $root.find('li.dr').each(function() {
                var $li = $(this);
                var $orphans = $li.children('li');
                if ($orphans.length && !$li.children('> ul.menu-v').length) {
                    $('<ul class="menu-v with-icons"></ul>').append($orphans).appendTo($li);
                }
            });

            var byId = {};
            $root.find('li.dr[id^="category-"]').each(function() {
                if (!byId[this.id]) {
                    byId[this.id] = [];
                }
                byId[this.id].push(this);
            });

            var matchesParent = function($li, parentId) {
                parentId = parseInt(parentId, 10) || 0;
                if (parentId === 0) {
                    return $li.parent('ul').parent().is('#s-category-list');
                }
                var $parentLi = $('#category-' + parentId);
                if (!$parentLi.length) {
                    return false;
                }
                return $.contains($parentLi.get(0), $li.get(0)) && !$li.is($parentLi);
            };

            $.each(byId, function(id, nodes) {
                if (nodes.length <= 1) {
                    return;
                }
                var $nodes = $(nodes);
                var $keep = $();
                $nodes.each(function() {
                    var $li = $(this);
                    if (matchesParent($li, $li.attr('data-parent-id'))) {
                        $keep = $li;
                        return false;
                    }
                });
                if (!$keep.length) {
                    $keep = $nodes.last();
                }
                $nodes.not($keep).remove();
            });
        }
    };
})(jQuery);
