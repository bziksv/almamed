<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
class shopCopyproductPlugin extends shopPlugin
{

    public function backendProduct($product)
    {
        if (!$product || !$product['id'] || ($product['id'] === 'new') || !$this->getSettings('product_page')) {
            return;
        }
        $html = '
<a style="margin-left: 20px" id="copyproduct" class="button" href="#">'._wp('Duplicate').'</a>
<script type="text/javascript">
$("#copyproduct").click(function () {
    $(this).replaceWith(\'<i class="icon16 loading"></i>\');
    $.post("?plugin=copyproduct&module=copy", {id: '.$product['id'].'}, function (response) {
        if (response.status == "ok") {
            $.wa.setHash("#/product/" + response.data.id + "/edit/");
        }
    }, "json");
    return false;
});
</script>';
        return array(
            'action_button' => $html
        );
    }


    public function backendProducts()
    {
        $name = _wp('Duplicate');
        $alert = _w('Please select at least one product');
        $html = <<<HTML
<div class="block" style="padding: 0 10px">
<ul class="menu-v with-icons compact p-no-photo-selected123 thumbs-view-menu">
<li>
    <a id="copyproduct" href="#"><i class="icon16 ss orders-all"></i>{$name}</a>
    <script type="text/javascript">
        $("#copyproduct").click(function () {
            var ids = [];
            if (!$.product_list.container.find('.product.selected').length) {
                alert("{$alert}");
                return false;
            }
            $.product_list.container.find('.product.selected').each(function () {
                ids.push($(this).data('product-id'));
            });
            $.post('?plugin=copyproduct&module=copy', {id: ids}, function (response) {
                $.products.dispatch();
            }, 'json');
            return false;
        });
    </script>
</li>
</ul>
</div>
HTML;
        return array('toolbar_section' => $html);
    }

    public function backendProductEdit($product)
    {
        $all = $this->getSettings('change_url');
        if ($all || preg_match('/\s\((\d+)\)$/', $product['name'], $m)) {
            if (!$all && substr($product['url'], - (strlen($m[1]) + 1)) != '_'.$m[1]) {
                return;
            }
            $html = <<<HTML
<script>
    $(function () {
        $.product.helper.onNameChange = function(element, animate, delay) {
            if ($('#s-product-frontend-url-input').is(':hidden')) {
                $('#s-product-frontend-url').trigger('editable');
                element.focus();
                this.data.url_helper.url = $($.product.options.form_selector).find(':input[name="product\[url\]"]').val();
            }
            if (this.data.url_helper.timer) {
                clearTimeout(this.data.url_helper.timer);
            }
            var target = $($.product.options.form_selector).find(':input[name="product\[url\]"]');
            var parent = target.parent();
            if (target.val() != this.data.url_helper.url) {
                $.shop.trace('$.product.onNameChange stop ' + this.data.url_helper.url + ' != ' + target.val());
                $($.product.options.form_selector).off('.product', ':input[name="product\[name\]"]');
                parent.find('.js-url-helper').hide();
            } else {
                if (animate) {
                    if (!parent.find('.js-url-helper').length) {
                        parent.append('<i class="icon16 loading js-url-helper"></i>');
                    } else {
                        parent.find('.js-url-helper').show();
                    }
                }
                var self = this;
                this.data.url_helper.timer = setTimeout(function() {
                    self.urlHelper(element, target, delay);
                }, delay || 500);
            }
        };
    });
</script>
HTML;
            return array('basics' => $html);
        }
    }
}
