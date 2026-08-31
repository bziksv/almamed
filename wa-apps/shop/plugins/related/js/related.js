(function ($) {

    function saveRelatedPluginFields() {
        $('input[name="title_related"]').each(function () {
            var $el = $(this);
            $.ajax({
                url: '?plugin=related&action=title',
                type: 'POST',
                async: false,
                data: {
                    product_id: $el.data('product_id'),
                    type: $el.data('type'),
                    title: $el.val()
                }
            });
        });

        var $view = $('#related-view');
        if ($view.length) {
            $.ajax({
                url: '?plugin=related&action=save',
                type: 'POST',
                async: false,
                data: {
                    product_id: $view.data('product_id'),
                    selected: $view.val()
                }
            });
        }
    }

    $('#add-related').off('click.related').on('click.related', function () {
        $.post('?plugin=related&action=get', { product_id: $(this).data('product_id') }, function (d) {
            if (d.status == 'ok') {
                $.shop.jsonPost(
                    '?module=product&action=relatedSave&id=' + d.data.product_id,
                    {
                        type: d.data.type,
                        product_id: d.data.product_id
                    },
                    function () {
                        window.location.reload();
                    }
                );
            }
        }, 'json');
        return false;
    });

    $('#related-view').off('change.related').on('change.related', function () {
        $.post('?plugin=related&action=save', {
            product_id: $(this).data('product_id'),
            selected: $(this).val()
        });
    });

    // Обычное «Сохранить» + кнопки Quick Editor («Сохранить и обновить/закрыть»)
    $(document)
        .off('click.relatedSave', '#s-product-save-button, #quickeditor-save-update, #quickeditor-save-close')
        .on('click.relatedSave', '#s-product-save-button, #quickeditor-save-update, #quickeditor-save-close', function () {
            saveRelatedPluginFields();
        });

    // saveData() (Ctrl+S и Quick Editor) на вкладке related
    if ($.product) {
        $.product.editTabRelatedSave = function () {
            saveRelatedPluginFields();
        };
    }

})(jQuery);
