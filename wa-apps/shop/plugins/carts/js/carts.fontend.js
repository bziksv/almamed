(function($, c, time, t2){


$[c] = $[c] || {
    _initialized:false,
    save_url:false,
    heartbeat_url:false,
    save_timer:false,
    bind_timer:false,
    heartbeat_timer:false,
    customer_data:{},
    selectors: {},
    init:function(save_url, heartbeat_url, customer_data) {
        if(this._initialized) {
            return;
        }
        this.initialized = true;

        this.save_url = save_url;
        this.heartbeat_url = heartbeat_url;
        this.customer_data = customer_data;

        this._init_selectors();

        $[c]._bind();

        $[c].heartbeat();
    },

    save:function(){
        /**
         * @var this - field
         */
        var $f = $(this).closest('form, .quickorder-form');

        if($f.length) {
            clearTimeout($[c].save_timer);
            $[c].save_timer = setTimeout(function () {
                var data = { };

                $f.find('[data-carts-field]').each(function (i, v) {
                    data[$(v).data('carts-field')] = $(v).val();
                });


                console.log($[c].save_url, data);
                if($[c].save_url && data) $.post($[c].save_url, data);
            }, t2)
        }
    },
    heartbeat:function () {
        if(this.heartbeat_url) {
            $.get(this.heartbeat_url);
        }
    },

    _bind:function() {
        $('input:not([data-carts-checked]),select:not([data-carts-checked])').each(function (index, field) {
            var $field = $(field);
            $field.attr('data-carts-checked', true);

            $.each($[c].selectors, function (key, s) {
                if($field.is(s.selector)) {
                    $field
                        .attr('data-carts-field', s.field)
                        .on('change blur', $[c].save);

                    if($[c].customer_data[key] && !$field.val()) {
                        $field.val($[c].customer_data[key]);
                    }

                    return false;
                }
            })
        });

        clearTimeout($[c].bind_timer);
        $[c].bind_timer = setTimeout($[c]._bind, time);
    },
    _init_selectors:function () {
        var i, key,
            fields = [ 'email', 'phone', 'firstname', 'lastname', 'middlename', 'name'];
        for(i in fields) {
            key = fields[i];
            $[c].selectors[key] = {
                field : 'customer['+key+']',
                selector : '[name="customer[' + key + ']"], [name="fields[' + key + ']"], ' +
                    '[name="quickorder_fields[' + key + ']"], [name="auth[data][' + key + ']"]'
            }
        }
    }
};
})(jQuery, 'shop_carts_plugin', 3000, 500);
