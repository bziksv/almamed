$(function() {

    $('#wa-plugins-content').on('change', '#selected-params_buy', function () {

        var self = $(this);

        $.post('?plugin=producthidden&module=save', {
            id : self.data('id'),
            code : self.data('code'),
            value : self.val()
        }, function (response) {

            if(response.status == "ok"){

                var icon = $('<i>').css({
                    position: 'absolute',
                    right: '-15px'
                }).addClass('icon10 yes');

                self.after(icon);
                self.next('i').delay('1000').fadeOut();
            }
        });
    });

    $('#wa-plugins-content').on('change', '#selected-all', function () {

        $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]').prop('checked', $(this).prop('checked'));
    });

    $('#wa-plugins-content').on('click', '.paging#producthidden a', function () {

        var page = $(this).data('page');
        $.get('?plugin=producthidden&module=settings', { page : page }, function (response) {

            $('#wa-plugins-content .double-padded').html(response);
            $('#wa-plugins-content .double-padded').find(`.paging a[data-page="${page}"]`).addClass('selected');
            console.log('product hidden load!');
        });
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="set-badge"] a, li[data-action="delete-badge"] a', function() {

        var li = $(this).closest('li');
        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{
            var products = {count : product_id.length, serialized : product_id.serializeArray()};

            if(li.data('type')){
                products.serialized.push({name : 'code', value : li.data('type')});
            }

            setBadge(products, li);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="set-custom-badge"] a', function() {
        $(this).parent().find('.textarea-wrapper').slideToggle('fast');
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="set-custom-badge"] input', function() {

        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        } else {
            var li = $(this).closest('li');
            var products = {count : product_id.length, serialized : product_id.serializeArray()};
            products.serialized.push({name : 'code', value : li.find('textarea').val()});

            setBadge(products, li);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="duplicate"] a', function() {

        var li = $(this).closest('li');
        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{
            var products = {count : product_id.length, serialized : product_id.serializeArray()};

            duplicateProducts(products, li);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="delete"] a', function() {

        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{

            var $data = [];
            product_id.each(function() {
                $data.push($(this).val());
            });

            var products = {count : product_id.length, product_id : $data};

            deleteProductsDialog(products);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="category"] a', function() {

        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{
            var products = {count : product_id.length, serialized : product_id.serializeArray()};

            categoriesDialog(products);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="set"] a', function() {

        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{
            var products = {count : product_id.length, serialized : product_id.serializeArray()};

            setsDialog(products);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="assign-tags"] a', function() {

        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{
            var products = {count : product_id.length, serialized : product_id.serializeArray()};

            assignTagsDialog(products);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="visibility"] a', function() {

        var li = $(this).closest('li');
        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{
            var products = {count : product_id.length, serialized : product_id.serializeArray()};

            visibilityDialog(products, li);
        }
        return false;
    });

    $('#wa-plugins-content').on('click', 'li[data-action="type"] a', function() {

        var product_id = $(this).closest('#wa-plugins-content').find('input[name="product_id[]"]:checked');
        if (!product_id.length) {
            alert($_('Please select at least one product'));
        }else{
            var products = {count : product_id.length, serialized : product_id.serializeArray()};

            typesDialog(products);
        }
        return false;
    });

});

function setBadge(products, $li) {

    var action = $li.data('action');
    if (action != 'set-custom-badge') {
        $li.parent().find('.textarea-wrapper').slideUp('fast');
    }

    $li.find('.loading').remove();
    $li.find('a').append('<span class="count"><i class="icon16 loading"></i></span>');

    // Hashmap of product ids used in jsonComplete()
    var is_selected = {};
    var everything_selected = false;
    $.each(products.serialized, function() {
        everything_selected = everything_selected || this.name == 'hash';
        is_selected[this.value] = 1;
    });

    // Badge deletion has a separate controller
    if (action == 'delete-badge') {
        $li.find('.loading').remove();
        $.shop.jsonPost('?module=product&action=badgeDelete', products.serialized, jsonComplete);
        return;
    }

    // Prepare data for badge saving contoller
    var badge_code;
    if (action == 'set-custom-badge') {
        badge_code = $li.find('textarea').val();
    } else {
        badge_code = $li.data('type');
    }

    var data = products.serialized;
    data.push({
        name: 'code',
        value: badge_code
    });

    // Save badge
    $.shop.jsonPost('?module=product&action=badgeSet', data, jsonComplete);

    // Helper to update DOM after badge has been saved
    function jsonComplete(r) {

        var badge_html = (action == 'delete-badge' ? null : r.data);
        $('#product-list [data-product-id]').each(function() {
            var $li = $(this);
            if (everything_selected || is_selected[$li.data('product-id')]) {
                if ($li.is('tr')) {
                    var $a = $li.find('.s-image a');
                    $a.find('.s-image-corner').remove();
                    badge_html && $a.prepend($('<div class="s-image-corner"></div>').html(badge_html));
                } else {
                    var $a = $li.find('.s-product-image a');
                    $a.find('.s-image-corner.top.right').remove();
                    badge_html && $a.append($('<div class="s-image-corner top right"></div>').html(badge_html));
                }
                $li.trigger('badge', [badge_html]);
            }
        });

        action == 'set-custom-badge' && $li.parent().find('.textarea-wrapper').slideUp('fast');
        $li.find('.loading').remove();
        $li.find('a').append(
            $('<span class="count"><i class="icon16 yes"></i></span>').animate({ opacity: 0 }, function() {
                $(this).remove();
            })
        );
    }
}

function duplicateProducts(products, $link) {
    var ids = [];
    var product;
    var hash = false;
    while (product = products.serialized.pop()) {
        if (product.name) {
            if (product.name == 'product_id[]') {
                ids.push(parseInt(product.value, 10));
            } else if (product.name == 'hash') {
                hash = product.value;
            }
        }
    }
    if (!hash && ids.length) {
        hash = 'id/' + ids.join(',');
    }
    if (hash) {
        $link.find('i.icon16').removeClass('split').addClass('loading');
        duplicate(hash, {
            'progress': function (data) {
                $link.attr('title', Math.round(100.0 * data.offset / data.total_count) + '%');
            },
            'finish': function (data, new_ids) {
                $link.attr('title', null);
                var $icon = $link.find('i.icon16');
                $icon.removeClass('loading').addClass('yes');
                setTimeout(function () {
                    $icon.removeClass('yes').addClass('split');
                }, 3000);

                $(document).one('product_list_init_view', function() {
                    var is_new = {};
                    $.each(new_ids, function(i, id) {
                        is_new[id] = 1;
                    });
                    $('#product-list [data-product-id]').each(function() {
                        var $this = $(this);
                        var id = $(this).data('product-id');
                        if (id && is_new[id]) {
                            $this.addClass('highlighted');
                        }
                    });
                });

                window.location.reload();
            },
            'error': function (data) {

            }
        });
    }
}

function duplicate(hash, options) {
    var params = {
        'hash': hash,
        'limit': 50,
        'offset': options.offset || 0
    };
    var url = '?module=products&action=duplicate';
    var self = this;
    var new_ids = [];

    $.shop.jsonPost(url, params, function (response) {
        if ((response.status || 'error') == 'ok') {
            new_ids = new_ids.concat(response.data.new_ids || []);
            if (response.data.offset < response.data.total_count) {
                options.offset = response.data.offset;
                self.duplicate(hash, options);
                options.progress(response.data || {});
            } else {
                options.finish(response.data || {}, new_ids);
            }
        }
    }, function (r) {
        if (r.errors) {
            alert(r.errors);
        }
    });
}

function deleteProductsDialog(products) {
    var showDialog = function () {
        $('#s-product-list-delete-products-dialog').waDialog({
            disableButtonsOnSubmit: true,
            onLoad: function () {
                $(this).find('.dialog-buttons i.loading').hide();
            },
            onSubmit: function (d) {
                $(this).find('.dialog-buttons i.loading').show();
                removeProduct($.extend(products, {
                    remove: ['products']
                }), function (r, not_allowed_ids) {
                    window.location.reload();
                });
                return false;
            }
        });
    };
    var d = $('#s-product-list-delete-products-dialog');
    var p = d.parent();
    if (!d.length) {
        p = $('<div></div>').appendTo('body');
    }
    p.load('?module=dialog&action=productsDelete&count=' + products.count, showDialog);
}

function removeProduct(options, finish) {
    var count = 100;
    var params = {};
    var url = '?module=products&action=deleteList';
    var not_allowed_ids = [];
    var process;
    if (options.product_id) {
        process = function () {
            if (options.product_id.length <= count) {
                params.get_lists = true;
            }
            params.product_id = options.product_id.splice(0, count);
            $.shop.jsonPost(url, params, function (r) {
                r.data.not_allowed && r.data.not_allowed.length && (not_allowed_ids = not_allowed_ids.concat(r.data.not_allowed));
                if (options.product_id.length) {
                    process();
                } else if (typeof finish === 'function') {
                    finish(r, not_allowed_ids);
                }
            });
        };
    } else {
        params.hash = options.hash || (this.collection_hash.join('/') || 'all');
        params.remove = $.isArray(options.remove) && options.remove.length ? options.remove : ['list'];
        if (params.remove.length == 1 && params.remove[0] == 'list') {
            process = function () {
                $.shop.jsonPost(url, params, finish);
            };
        } else {
            params.count = count;
            var rest_count = null; // previous rest count
            process = function () {
                $.shop.jsonPost(url, params, function (r) {
                    r.data.not_allowed && r.data.not_allowed.length && (not_allowed_ids = not_allowed_ids.concat(r.data.not_allowed));
                    if (r.data.rest_count > 0 && rest_count != r.data.rest_count) {
                        process();
                    } else if (typeof finish === 'function') {
                        finish(r, not_allowed_ids);
                    }
                });
            };
        }
    }

    process();

}

function categoriesDialog(products) {
    var d = $('#s-categories');
    var sidebar = this.sidebar;
    var product_list = this.container;
    var showDialog = function () {
        $('#s-categories').waDialog({
            disableButtonsOnSubmit: true,
            onLoad: function () {
                var self = $(this);
                self.find('.dialog-content h1 span').text('(' + products.count + ')').show();
                self.find('.dialog-buttons i.loading').hide();
                self.find('input[name=new_category_name]').val('');
                self.find('input[name=new_category]').attr('checked', false);
            },
            onSubmit: function (d) {
                // addToCategories
                var form = d.find('form');
                form.find('.dialog-buttons i.loading').show();
                $.shop.jsonPost(form.attr('action'), form.serializeArray().concat(products.serialized), function (r) {

                    // add new category to sidebar
                    if (r.data.new_category) {
                        $('#s-category-list ul:first').trigger('add',
                            [r.data.new_category, 'category', '#/products/category_id=' + r.data.new_category + '&view=' + $.product_list.options.view]);
                    }

                    // update cagegories in sidebar
                    if (r.data.categories) {
                        window.location.reload();
                    }

                    form.find('input:checked').attr('checked', false);
                    d.trigger('close');
                });
                return false;
            }
        });
    };

    // no cache dialog
    if (d.length) {
        d.parent().remove();
    }

    var p = $('<div></div>').appendTo('body');
    p.load('?module=dialog&action=categories', showDialog);
}

function setsDialog(products) {
    var d = $('#s-sets');
    var sidebar = this.sidebar;
    var product_list = this.container;
    var showDialog = function () {
        $('#s-sets').waDialog({
            disableButtonsOnSubmit: true,
            onLoad: function () {
                var self = $(this);
                self.find('.dialog-content h1 span').text('(' + products.count + ')').show();
                self.find('.dialog-buttons i.loading').hide();
                self.find('input[name=new_set_name]').val('');
                self.find('input[name=new_set]').attr('checked', false);
            },
            onSubmit: function (d) {
                // addToSets
                var form = d.find('form');
                form.find('.dialog-buttons i.loading').show();
                $.shop.jsonPost(form.attr('action'), form.serializeArray().concat(products.serialized), function (r) {

                    // add new category to sidebar
                    if (r.data.new_set) {
                        $('#s-set-list ul:first').trigger('add',
                            [r.data.new_set, 'set', '#/products/set_id=' + r.data.new_set + '&view=' + $.product_list.options.view]);
                    }

                    // update cagegories in sidebar
                    if (r.data.sets) {
                        window.location.reload();
                    }
                    form.find('input:checked').attr('checked', false);
                    d.trigger('close');
                });
                return false;
            }
        });
    };

    // no cache dialog
    if (d.length) {
        d.parent().remove();
    }

    var p = $('<div></div>').appendTo('body');
    p.load('?module=dialog&action=sets', showDialog);
}

function assignTagsDialog(products) {
    var d = $('#s-assign-tags');
    var showDialog = function () {
        $('#s-assign-tags').waDialog({
            disableButtonsOnSubmit: true,
            onLoad: function () {
                var self = $(this);
                self.find('.dialog-content h1 span').text('(' + products.count + ')').show();
                self.find('.dialog-buttons i.loading').hide();
            },
            onSubmit: function (d) {
                var self = $(this);
                var $tags_input = self.find('#s-assign-tags-list_tag');
                if ($tags_input.length) {
                    var e = jQuery.Event("keypress", {
                        which: 13
                    });
                    $tags_input.trigger(e);
                }

                self.find('.dialog-buttons i.loading').show();
                var url = self.attr('action');
                setTimeout(function () {
                    // assignTags
                    var data = self.serializeArray().concat(products.serialized);
                    $.shop.jsonPost(url, data, function (r) {
                        if (r.data.cloud = 'search') {
                            d.trigger('close');
                        } else if(r.data.cloud) {
                            $('#s-tag-cloud').trigger('update', [r.data.cloud]);
                        }
                        d.trigger('close');
                    }, function () {
                        d.trigger('close');
                    });
                }, 10);
                return false;
            }
        });
    };

    // no cache dialog
    if (d.length) {
        d.remove();
    }

    // use post-method instead of get-method because of potential long list of product ids
    $.post('?module=dialog&action=assignTags', products.serialized, function (html) {
        $('body').append(html);
        showDialog();
    });
}

function visibilityDialog(products, $li) {
    // Sanity check...
    if (!$.isArray(products.serialized)) {
        return false;
    }

    var $icon = $li.find('i.icon16');
    if (!$icon.hasClass('loading')) {
        var $wrapper = $('#visibility-dialog-wrapper');
        if (!$wrapper.length) {
            $wrapper = $('<div id="visibility-dialog-wrapper">').appendTo('#wa-plugins-content');
        }

        var old_icon_class = $icon.attr('class');
        $icon.attr('class', 'icon16 loading');
        $wrapper.data('products', products).load('?module=dialog&action=visibility', function() {
            $icon.attr('class', old_icon_class);
            $wrapper.find('.button.green').click(function () {
                setTimeout(function () {
                    window.location.reload();
                }, 1000);
            });
        });
    }
}

function typesDialog(products) {
    var d = $('#s-types');
    var product_list = this.container;
    var sidebar = this.sidebar;
    var showDialog = function () {
        $('#s-types').waDialog({
            disableButtonsOnSubmit: true,
            onLoad: function () {
                $(this).find('.dialog-buttons i.loading').hide();
            },
            onSubmit: function (d) {
                var form = $(this);
                form.find('.dialog-buttons i.loading').show();
                $.shop.jsonPost(form.attr('action'), form.serializeArray().concat(products.serialized), function (r) {
                    window.location.reload();
                });
                return false;
            }
        });
    };
    var p = d.parent();
    if (!d.length) {
        p = $('<div></div>').appendTo('body');
        p.load('?module=dialog&action=types', showDialog);
    } else {
        showDialog();
    }
}
